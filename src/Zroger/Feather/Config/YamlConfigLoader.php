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

use Symfony\Component\Yaml\Yaml;

class YamlConfigLoader
{
    public function load($file, $type = null)
    {
        $values = Yaml::parse($file);

        // relative paths in yaml config are relative to the yaml file.
        $paths = array('document_root', 'server_root');
        $basepath = dirname($file);

        foreach ($paths as $path) {
            if (isset($values[$path])) {
                $values[$path] = $this->resolveRelativePath($values[$path], $basepath);
            }
        }

        return $values;
    }

    protected function resolveRelativePath($path, $basepath)
    {
        if (strpos($path, '/') !== 0) {
            $path = $basepath . '/' . $path;
        }
        return realpath($path);
    }
}
