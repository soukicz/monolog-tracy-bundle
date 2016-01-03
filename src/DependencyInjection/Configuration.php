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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('monolog_tracy');

		$rootNode->children()
			->arrayNode('handlers')
				->useAttributeAsKey('name')
				->canBeUnset()
				->prototype('array')
					->children()
						->scalarNode('log_directory')->defaultValue('%kernel.logs_dir%/blueScreen')->end()
						->scalarNode('level')->defaultValue('DEBUG')->end()
						->booleanNode('bubble')->defaultTrue()->end()
					->end()
				->end()
			->end()
		->end();

		return $treeBuilder;
	}

}
