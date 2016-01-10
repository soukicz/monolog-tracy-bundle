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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MonologTracyExtension extends \Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension implements \Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface
{

	const BLUESCREEN_FACTORY_SERVICE_ID = 'nella.monolog_tracy.tracy.blue_screen_factory';
	const BLUESCREEN_HANDLER_SERVICE_ID = 'nella.monolog_tracy.blue_screen_handler';

	const LOG_DIRECTORY_PARAMETER = 'nella.monolog_tracy.log_directory';
	const HANDLER_BUBBLE_PARAMETER = 'nella.monolog_tracy.blue_screen_handler.bubble';
	const HANDLER_LEVEL_PARAMETER = 'nella.monolog_tracy.blue_screen_handler.level';

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

		$this->createTemporaryHandlerService($container);

		$monologConfigList = $container->getExtensionConfig('monolog');
		foreach ($monologConfigList as $config) {
			if (!isset($config['handlers'])) {
				continue;
			}

			$handlers = array_filter($config['handlers'], function (array $handler) {
				return is_array($handler) && isset($handler['type']) && $handler['type'] === 'tracyBlueScreen';
			});

			// Create config
			$container->loadFromExtension('monolog', $this->createMonologConfigEntry($handlers));
		}
	}

	/**
	 * @param mixed[] $config
	 * @param ContainerBuilder $container
	 */
	public function loadInternal(array $config, ContainerBuilder $container)
	{
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
		$loader->load('services.yml');

		$container->setParameter(static::LOG_DIRECTORY_PARAMETER, $config[Configuration::LOG_DIRECTORY]);
		$container->setParameter(static::HANDLER_BUBBLE_PARAMETER, $config[Configuration::HANDLER_BUBBLE]);
		$container->setParameter(static::HANDLER_LEVEL_PARAMETER, $config[Configuration::HANDLER_LEVEL]);

		$this->setupBlueScreenFactory($container);
	}

	private function createTemporaryHandlerService(ContainerBuilder $container)
	{
		$container->setDefinition(
			static::BLUESCREEN_HANDLER_SERVICE_ID,
			new Definition('stdClass')
		);
	}

	/**
	 * Adjusts Monolog configuration to be valid by replacing 'tracyBlueScreen' type by 'service'
	 * and adding a service id.
	 *
	 * @param array $handlers
	 * @return array
	 */
	private function createMonologConfigEntry(array $handlers)
	{
		$config = [];
		foreach ($handlers as $name => $value) {
			$value['type'] = 'service';
			$value['id'] = static::BLUESCREEN_HANDLER_SERVICE_ID;

			$config[$name] = $value;
		}

		return [
			'handlers' => $config,
		];
	}

	private function setupBlueScreenFactory(ContainerBuilder $container)
	{
		$serviceId = $this->getBlueScreenFactoryServiceId($container);
		$definition = $container->getDefinition($serviceId);

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

		$container->setDefinition($serviceId, $definition);
	}

	/**
	 * @param ContainerBuilder $container
	 * @return string
	 */
	private function getBlueScreenFactoryServiceId(ContainerBuilder $container)
	{
		if ($container->hasDefinition(static::BLUESCREEN_FACTORY_SERVICE_ID)) {
			return static::BLUESCREEN_FACTORY_SERVICE_ID;
		} else {
			return (string) $container->getAlias(static::BLUESCREEN_FACTORY_SERVICE_ID);
		}
	}

}
