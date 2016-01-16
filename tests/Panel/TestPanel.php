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

namespace Nella\MonologTracyBundle\Panel;

use Tracy\Dumper;

class TestPanel extends \Nella\MonologTracyBundle\Panel\Panel
{

	/**
	 * @param \Exception|\Throwable $exception
	 * @return bool
	 */
	public function isSupported($exception)
	{
		if ($exception instanceof \Nella\MonologTracyBundle\Exception) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param \Exception|\Throwable $exception
	 * @return string
	 */
	public function getTab($exception)
	{
		return get_class($exception);
	}

	/**
	 * @param \Exception|\Throwable $exception
	 * @return string
	 */
	public function getPanel($exception)
	{
		return Dumper::toHtml($exception);
	}

}
