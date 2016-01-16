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

abstract class Panel
{

	/**
	 * @param \Exception $exception
	 * @return bool
	 */
	abstract public function isSupported(\Exception $exception);

	/**
	 * @param \Exception $exception
	 * @return string
	 */
	abstract public function getTab(\Exception $exception);

	/**
	 * @param \Exception $exception
	 * @return string
	 */
	abstract public function getPanel(\Exception $exception);

	/**
	 * @param \Exception $exception
	 * @return string[]
	 */
	final public function __invoke(\Exception $exception)
	{
		if (!$this->isSupported($exception)) {
			return [];
		}

		return [
			'tab' => $this->getTab($exception),
			'panel' => $this->getPanel($exception),
		];
	}

}
