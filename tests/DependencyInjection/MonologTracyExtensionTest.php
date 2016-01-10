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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MonologTracyExtensionTest extends \Nella\MonologTracyBundle\TestCase
{

	/** @var ContainerBuilder */
	private $container;

	/** @var YamlFileLoader */
	private $yamlLoader;

	/** @var string */
	private $logDirectory;

	protected function setup()
	{
		$this->container = new ContainerBuilder();
		$this->container->registerExtension(new MonologExtension());
		$this->container->registerExtension(new MonologTracyExtension());

		$this->yamlLoader = new YamlFileLoader($this->container, new FileLocator(__DIR__ . '/Fixtures'));

		$this->logDirectory = sys_get_temp_dir() . '/' . getmypid() . microtime() . '-monologExtensionsTest';
		@mkdir($this->logDirectory);

		$this->container->setParameter('kernel.environment', 'test');
		$this->container->setParameter('kernel.logs_dir', $this->logDirectory);
	}

	/**
	 * @expectedException \Nella\MonologTracyBundle\DependencyInjection\MissingMonologExtensionException
	 */
	public function testThrowsExceptionWhenMonologIsMissing()
	{
		$containerBuilder = new ContainerBuilder();
		$containerBuilder->registerExtension(new MonologTracyExtension());

		$containerBuilder->compile();
	}

	public function testEditsMonologConfiguration()
	{
		$this->loadFixture('monolog.yml');
		$this->loadFixture('minimalBlueScreen.yml');
		$this->compile();

		$config = $this->getConfig('monolog');
		$handlers = $config['handlers'];
		$this->assertSame([
			'type' => 'service',
			'id' => 'nella.monolog_tracy.blue_screen_handler',
		], $handlers['blueScreen']);
	}

	public function testSavesConfiguration()
	{
		$this->loadFixture('monolog.yml');
		$this->loadFixture('fullBlueScreen.yml');
		$this->compile();

		$config = $this->getConfig('monolog');
		$handlers = $config['handlers'];
		$this->assertEquals([
			'type' => 'service',
			'id' => 'nella.monolog_tracy.blue_screen_handler',
			'level' => 'critical',
			'bubble' => FALSE,
			'channels' => [
				'channel',
			],
		], $handlers['blueScreen']);
	}

	public function testEditsOnlyBlueScreens()
	{
		$this->loadFixture('monolog.yml');
		$this->loadFixture('minimalBlueScreen.yml');
		$this->compile();

		$config = $this->getConfig('monolog');
		$handlers = $config['handlers'];
		$this->assertSame([
			'type' => 'stream',
			'path' => '%kernel.logs_dir%/%kernel.environment%.log',
			'level' => 'debug',
		], $handlers['main']);
	}

	public function testNoHandlers()
	{
		$this->loadFixture('noHandlers.yml');
		$this->compile();

		$config = $this->getConfig('monolog');
		$this->assertCount(0, $config['handlers']);
	}

	private function getConfig($extension)
	{
		$configs = $this->container->getExtensionConfig($extension);
		$config = [];
		foreach ($configs as $tmp) {
			$config = array_replace_recursive($config, $tmp);
		}
		return $config;
	}

	private function compile()
	{
		$this->container->getCompiler()->compile($this->container);
	}

	/**
	 * @param string $name
	 */
	private function loadFixture($name)
	{
		$this->yamlLoader->load($name);
	}

}
