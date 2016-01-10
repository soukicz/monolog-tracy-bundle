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

use Symfony\Bundle\MonologBundle\DependencyInjection\MonologExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MonologTracyExtensionTest extends \Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase
{

	protected function setUp()
	{
		parent::setUp();

		$logDirectory = sys_get_temp_dir() . '/' . getmypid() . microtime() . '-monologExtensionsTest';
		@mkdir($logDirectory);

		$this->container->setParameter('kernel.environment', 'test');
		$this->container->setParameter('kernel.logs_dir', $logDirectory);
	}

	/**
	 * @return \Symfony\Component\DependencyInjection\Extension\ExtensionInterface[]
	 */
	protected function getContainerExtensions()
	{
		return [
			new MonologExtension(),
			new MonologTracyExtension(),
		];
	}

	public function testParameters()
	{
		$this->load();

		$this->assertContainerBuilderHasParameter(MonologTracyExtension::LOG_DIRECTORY_PARAMETER);
		$this->assertContainerBuilderHasParameter(MonologTracyExtension::HANDLER_BUBBLE_PARAMETER);
		$this->assertContainerBuilderHasParameter(MonologTracyExtension::HANDLER_LEVEL_PARAMETER);

		$this->compile();
	}

	public function testHandlerService()
	{
		$this->load();

		$this->assertContainerBuilderHasService(MonologTracyExtension::BLUESCREEN_HANDLER_SERVICE_ID);

		$this->compile();
	}

	public function testFactoryService()
	{
		$this->load();

		$this->assertContainerBuilderHasService(MonologTracyExtension::BLUESCREEN_FACTORY_SERVICE_ID);

		$this->compile();
	}

	public function testFactoryServiceNoAlias()
	{
		$this->loadConfigs([
			'blueScreenFactoryNoAlias.yml',
		]);

		$this->load();

		$this->assertContainerBuilderHasService(MonologTracyExtension::BLUESCREEN_FACTORY_SERVICE_ID);

		$this->compile();
	}

	private function loadConfigs(array $configs)
	{
		$loader = new YamlFileLoader($this->container, new FileLocator(__DIR__ . '/fixtures'));
		foreach ($configs as $config) {
			$loader->load($config);
		}
	}

}
