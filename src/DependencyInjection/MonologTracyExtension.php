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
	const BLUESCREEN_SERVICE_ID = 'nella.monolog_tracy.blue_screen';
	const LOGGER_HELPER_SERVICE_ID = 'nella.monolog_tracy.tracy.logger_helper';

	const LOG_DIRECTORY_PARAMETER = 'nella.monolog_tracy.log_directory';
	const HANDLER_BUBBLE_PARAMETER = 'nella.monolog_tracy.blue_screen_handler.bubble';
	const HANDLER_LEVEL_PARAMETER = 'nella.monolog_tracy.blue_screen_handler.level';

	const HANDLER_NAME = 'tracyBlueScreen';

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
				return is_array($handler) && isset($handler['type']) && $handler['type'] === static::HANDLER_NAME;
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

		$this->setupParameters($container, $config);
		$this->setupAliases($container);

		$this->setupBlueScreenFactory(
			$container,
			$config[Configuration::INFO_ITEMS],
			$config[Configuration::PANELS],
			$config[Configuration::COLLAPSE_PATHS]
		);
	}

	/**
	 * @param ContainerBuilder $container
	 * @param mixed[] $config
	 */
	private function setupParameters(ContainerBuilder $container, array $config)
	{
		$container->setParameter(static::LOG_DIRECTORY_PARAMETER, $config[Configuration::LOG_DIRECTORY]);
		$container->setParameter(static::HANDLER_BUBBLE_PARAMETER, $config[Configuration::HANDLER_BUBBLE]);
		if (is_int($config[Configuration::HANDLER_LEVEL])) {
			$container->setParameter(static::HANDLER_LEVEL_PARAMETER, $config[Configuration::HANDLER_LEVEL]);
		} else {
			$container->setParameter(
				static::HANDLER_LEVEL_PARAMETER,
				constant(sprintf('Monolog\Logger::%s', strtoupper($config[Configuration::HANDLER_LEVEL])))
			);
		}
	}

	/**
	 * @param ContainerBuilder $container
	 */
	private function setupAliases(ContainerBuilder $container)
	{
		if (!$container->hasDefinition(static::BLUESCREEN_FACTORY_SERVICE_ID)) {
			$container->setAlias(
				static::BLUESCREEN_FACTORY_SERVICE_ID,
				sprintf('%s.default', static::BLUESCREEN_FACTORY_SERVICE_ID)
			);
		}
		if (!$container->hasDefinition(static::BLUESCREEN_HANDLER_SERVICE_ID)) {
			$container->setAlias(
				static::BLUESCREEN_HANDLER_SERVICE_ID,
				sprintf('%s.default', static::BLUESCREEN_HANDLER_SERVICE_ID)
			);
		}
		if (!$container->hasDefinition(static::BLUESCREEN_SERVICE_ID)) {
			$container->setAlias(
				static::BLUESCREEN_SERVICE_ID,
				sprintf('%s.default', static::BLUESCREEN_SERVICE_ID)
			);
		}
		if (!$container->hasDefinition(static::LOGGER_HELPER_SERVICE_ID)) {
			$container->setAlias(
				static::LOGGER_HELPER_SERVICE_ID,
				sprintf('%s.default', static::LOGGER_HELPER_SERVICE_ID)
			);
		}
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

	/**
	 * @param ContainerBuilder $container
	 * @param string[] $infoItems
	 * @param mixed[) $panels
	 * @param string[] $collapsePaths
	 */
	private function setupBlueScreenFactory(ContainerBuilder $container, array $infoItems, array $panels, array $collapsePaths)
	{
		$serviceId = $this->getBlueScreenFactoryServiceId($container);
		$definition = $container->getDefinition($serviceId);

		$infoItems = $this->setupDefaultInfoItems($infoItems);

		$this->processInfoItems($definition, $infoItems);
		$this->processPanels($definition, $panels);
		$this->processCollapsePaths($definition, $collapsePaths);

		$container->setDefinition($serviceId, $definition);
	}

	/**
	 * @param string[] $infoItems
	 * @return string[]
	 */
	private function setupDefaultInfoItems(array $infoItems)
	{
		if (class_exists(\Twig_Environment::class)) { // Twig version
			array_unshift($infoItems, sprintf(
				'Twig %s',
				\Twig_Environment::VERSION
			));
		}
		if (class_exists(\Doctrine\ORM\Version::class)) { // Doctrine version
			array_unshift($infoItems, sprintf(
				'Doctrine ORM %s',
				\Doctrine\ORM\Version::VERSION
			));
		}
		if (class_exists(\Symfony\Component\HttpKernel\Kernel::class)) { // Symfony version
			array_unshift($infoItems, sprintf(
				'Symfony %s',
				\Symfony\Component\HttpKernel\Kernel::VERSION
			));
		}

		return $infoItems;
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

	/**
	 * @param Definition $definition
	 * @param string[] $infoItems
	 */
	private function processInfoItems(Definition $definition, array $infoItems)
	{
		foreach ($infoItems as $info) {
			$definition->addMethodCall('registerInfo', [$info]);
		}
	}

	/**
	 * @param Definition $definition
	 * @param callable[] $panels
	 */
	private function processPanels(Definition $definition, array $panels)
	{
		foreach ($panels as $panel) {
			$definition->addMethodCall('registerPanel', [$panel]);
		}
	}

	/**
	 * @param Definition $definition
	 * @param string[] $collapsePaths
	 */
	private function processCollapsePaths(Definition $definition, array $collapsePaths)
	{
		foreach ($collapsePaths as $collapsePath) {
			$definition->addMethodCall('registerCollapsePath', [$collapsePath]);
		}
	}

}
