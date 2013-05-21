<?php

/*
 * This file is part of the Feather package.
 *
 * (c) Roger López <roger@zroger.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zroger\Feather\Config;

use Symfony\Component\Process\ExecutableFinder;

/**
 * Executable finder capable of finding an executable that may have different
 * names on different systems.
 *
 * @author Roger López <roger@zroger.com>
 */
class MultiExecutableFinder extends ExecutableFinder
{
    /**
     * @inheritdoc
     */
    public function find($names, $default = null, array $extraDirs = array())
    {
        foreach ((array)$names as $name) {
            $executable = parent::find($name, $default, $extraDirs);
            if ($executable != $default) {
                return $executable;
            }
        }
        return $default;
    }
}
