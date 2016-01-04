<?php
/**
 * This file is part of the Nella Project (https://monolog-tracy.nella.io).
 *
 * Copyright (c) 2014 Pavel Kučera (http://github.com/pavelkucera)
 * Copyright (c) Patrik Votoček (https://patrik.votocek.cz)
 *
 * For the full copyright and license information,
 * please view the file LICENSE.md that was distributed with this source code.
 */

namespace Nella\MonologTracyBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class MonologTracyExtension extends Extension implements PrependExtensionInterface
{

	/**
	 * Steals Monolog configuration, goes through handlers and adjusts config
	 * of blue screen handlers.
	 *
	 * @param ContainerBuilder $container
	 */
	public function prepend(ContainerBuilder $container)
	{
		if (!$container->hasExtension('monolog')) {
			throw new \Nella\MonologTracyBundle\DependencyInjection\MissingMonologExtensionException();
		}

		$monologConfigList = $container->getExtensionConfig('monolog');
		foreach ($monologConfigList as $config) {
			if (!isset($config['handlers'])) {
				continue;
			}

			$handlers = array_filter($config['handlers'], function(array $handler) {
				return is_array($handler) && isset($handler['type']) && $handler['type'] === 'blue screen';
			});

			// Create config
			$container->loadFromExtension('monolog_tracy', $this->createConfigEntry($handlers));
			$container->loadFromExtension('monolog', $this->createMonologConfigEntry($handlers));
		}
	}

	/**
	 * @param array $configs
	 * @param ContainerBuilder $container
	 */
	public function load(array $configs, ContainerBuilder $container)
	{
		$configuration = $this->getConfiguration($configs, $container);
		$config = $this->processConfiguration($configuration, $configs);

		foreach ($config['handlers'] as $name => $handler) {
			$this->buildHandler($container, $name, $handler);
		}

		$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/config'));
		$loader->load('parameters.yml');
		$loader->load('blueScreenFactory.yml');
		$loader->load('blueScreen.yml');
		$loader->load('loggerHelper.yml');
		$loader->load('blueScreenHandler.yml');

		$this->setupBlueScreenFactory($container);

		$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');
	}

	private function setupBlueScreenFactory(ContainerBuilder $container)
	{
		$definition = new DefinitionDecorator('nella.monolog_tracy.tracy.blue_screen_factory_prototype');

		if (class_exists(\Symfony\Component\HttpKernel\Kernel::class)) { // Symfony version
			$definition->addMethodCall('registerInfo', [sprintf(
				'Symfony %s',
				\Symfony\Component\HttpKernel\Kernel::VERSION
			)]);
		}
		if (class_exists(\Twig_Environment::class)) { // Twig version
			$definition->addMethodCall('registerInfo', [sprintf(
				'Twig %s',
				\Twig_Environment::VERSION
			)]);
		}
		if (class_exists(\Doctrine\ORM\Version::class)) { // Twig version
			$definition->addMethodCall('registerInfo', [sprintf(
				'Doctrine ORM %s',
				\Doctrine\ORM\Version::VERSION
			)]);
		}

		$container->setDefinition('nella.monolog_tracy.tracy.blue_screen_factory', $definition);
	}

	/**
	 * Transforms Monolog-like configuration of handlers into
	 *
	 * @param array $handlers
	 * @return array
	 */
	private function createConfigEntry(array $handlers)
	{
		$config = array_map(function(array $handler) {
			// Transform Monolog-like configuration
			if (isset($handler['path'])) {
				$handler['log_directory'] = $handler['path'];
			}

			// Remove unnecessary fields
			unset(
				$handler['type'],
				$handler['path'],
				$handler['channels']
			);
			return $handler;
		}, $handlers);

		return array('handlers' => $config);
	}

	/**
	 * Adjusts Monolog configuration to be valid by replacing 'blue screen' type by 'service'
	 * and adding a service id.
	 *
	 * @param array $handlers
	 * @return array
	 */
	private function createMonologConfigEntry(array $handlers)
	{
		$names = array_keys($handlers);
		if (!$names) {
			return array();
		}
		$getNameCallback = array($this, 'getHandlerName');
		$config = array_combine($names, array_map(function($name) use ($getNameCallback) {
			return array(
				'type' => 'service',
				'id' => call_user_func($getNameCallback, $name),
			);
		}, $names));
		return array('handlers' => $config);
	}

	/**
	 * @param ContainerBuilder $container
	 * @param string $name
	 * @param array $handler
	 */
	private function buildHandler(ContainerBuilder $container, $name, array $handler)
	{
		$definition = new DefinitionDecorator('kucera.monolog.handler.blue_screen_handler_prototype');
		if (isset($handler['log_directory'])) {
			$definition->replaceArgument(1, $handler['log_directory']);
		}
		if (isset($handler['level'])) {
			$level = $this->levelToMonologConstant($handler['level']);
			$definition->replaceArgument(1, $level);
		}
		if (isset($handler['bubble'])) {
			$definition->replaceArgument(2, $handler['bubble']);
		}

		$container->setDefinition($this->getHandlerName($name), $definition);
	}

	/**
	 * @param $level
	 * @return int
	 */
	private function levelToMonologConstant($level)
	{
		return is_int($level) ? $level : constant('Monolog\Logger::' . strtoupper($level));
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function getHandlerName($name)
	{
		return "kucera.monolog.blue_screen_handlers.$name";
	}

}
