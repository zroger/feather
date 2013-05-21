<?php

/*
 * This file is part of the Feather package.
 *
 * (c) Roger López <roger@zroger.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zroger\Feather\Log;

interface LogReaderInterface
{
    /**
     * Get the next line from the log.
     * @return Line The next message from the log as a Line instance.
     */
    public function nextLine();

    /**
     * Get all remaining lines from the log.
     * @return array An array of the Line instances.
     */
    public function allLines();
}
