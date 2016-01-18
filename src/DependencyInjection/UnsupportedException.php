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

class UnsupportedException extends \RuntimeException implements \Nella\MonologTracyBundle\DependencyInjection\Exception
{

	public function __construct($message, \Exception $previous = NULL)
	{
		parent::__construct($message, 0, $previous);
	}

}
