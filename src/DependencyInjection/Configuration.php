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

use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements \Symfony\Component\Config\Definition\ConfigurationInterface
{

	const ROOT_NAME = 'monolog_tracy';

	const LOG_DIRECTORY = 'log_directory';
	const HANDLER_BUBBLE = 'handler_bubble';
	const HANDLER_LEVEL = 'handler_level';

	const INFO_ITEMS = 'info_items';
	const PANELS = 'panels';
	const COLLAPSE_PATHS = 'collapse_paths';

	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root(static::ROOT_NAME);
		$rootNode->addDefaultsIfNotSet();

		$this->addLogDirectory($rootNode);
		$this->addHandlerBubble($rootNode);
		$this->addHandlerLevel($rootNode);
		$this->addInfoItems($rootNode);
		$this->addPanels($rootNode);
		$this->addCollapsePaths($rootNode);

		return $treeBuilder;
	}

	private function addLogDirectory(ArrayNodeDefinition $rootNode)
	{
		$rootNode
			->children()
				->scalarNode(static::LOG_DIRECTORY)
					->defaultValue('%kernel.logs_dir%/tracy')
				->end()
			->end();
	}

	private function addHandlerBubble(ArrayNodeDefinition $rootNode)
	{
		$rootNode
			->children()
				->scalarNode(static::HANDLER_BUBBLE)
					->defaultValue(TRUE)
				->end()
			->end();
	}

	private function addHandlerLevel(ArrayNodeDefinition $rootNode)
	{
		$rootNode
			->children()
				->scalarNode(static::HANDLER_LEVEL)
					->defaultValue(Logger::DEBUG)
				->end()
			->end();
	}

	private function addInfoItems(ArrayNodeDefinition $rootNode)
	{
		$rootNode
			->fixXmlConfig('info_item')
			->children()
				->arrayNode(static::INFO_ITEMS)
					->prototype('scalar')
				->end()
			->end();
	}

	private function addPanels(ArrayNodeDefinition $rootNode)
	{
		$rootNode
			->fixXmlConfig('panel')
			->children()
				->arrayNode(static::PANELS)
					->prototype('scalar')
				->end()
			->end();
	}

	private function addCollapsePaths(ArrayNodeDefinition $rootNode)
	{
		$rootNode
			->fixXmlConfig('collapse_path')
			->children()
				->arrayNode(static::COLLAPSE_PATHS)
					->prototype('scalar')
				->end()
			->end();
	}

}
