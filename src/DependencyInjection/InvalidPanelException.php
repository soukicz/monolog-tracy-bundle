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

class InvalidPanelException extends \RuntimeException implements \Nella\MonologTracyBundle\DependencyInjection\Exception
{

	/**
	 * @param string|mixed[] $panel
	 * @param \Exception|NULL $previous
	 */
	public function __construct($panel, \Exception $previous = NULL)
	{
		if (!is_string($panel)) {
			$panel = var_export($panel, TRUE);
		}
		parent::__construct(sprintf('This `%s` is not valid panel.', $panel), 0, $previous);
	}

}
