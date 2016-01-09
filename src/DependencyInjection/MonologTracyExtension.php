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

		$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/config'));
		$loader->load('parameters.yml');
		$loader->load('services.yml');

		$monologConfigList = $container->getExtensionConfig('monolog');
		foreach ($monologConfigList as $config) {
			if (!isset($config['handlers'])) {
				continue;
			}

			$handlers = array_filter($config['handlers'], function(array $handler) {
				return is_array($handler) && isset($handler['type']) && $handler['type'] === 'tracyBlueScreen';
			});

			// Create config
			$container->loadFromExtension('monolog', $this->createMonologConfigEntry(
				$handlers,
				'nella.monolog_tracy.blue_screen_handler'
			));
		}
	}

	/**
	 * @param array $configs
	 * @param ContainerBuilder $container
	 */
	public function load(array $configs, ContainerBuilder $container)
	{
		$this->setupBlueScreenFactory($container);
	}

	private function setupBlueScreenFactory(ContainerBuilder $container)
	{
		$definition = $container->getDefinition('nella.monolog_tracy.tracy.blue_screen_factory');

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
	 * Adjusts Monolog configuration to be valid by replacing 'blue screen' type by 'service'
	 * and adding a service id.
	 *
	 * @param array $handlers
	 * @return array
	 */
	private function createMonologConfigEntry(array $handlers, $serviceId)
	{
		$config = [];
		foreach ($handlers as $name => $value) {
			$value['type'] = 'service';
			$value['id'] = $serviceId;

			$config[$name] = $value;
		}

		return [
			'handlers' => $config,
		];
	}

}
