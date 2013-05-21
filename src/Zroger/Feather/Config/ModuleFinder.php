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

/**
 * Apache module finder.
 *
 * @author Roger López <roger@zroger.com>
 */
class ModuleFinder
{
    protected $paths = array();

    private $defaults = array(
        '/usr/libexec/apache2',       // osx
        '/usr/lib64/httpd/modules',   // centos
        '/usr/lib/apache2/modules'    // ubuntu
    );

    public function __construct($useDefaults = true)
    {
        if ($useDefaults) {
            $this->useDefaultPaths();
        }
    }

    public function useDefaultPaths()
    {
        $this->paths = $this->defaults;

        // PHP 5.3 from josegonzalez/php/php53 homebrew tap.
        if (exec('which brew')) {
            if ($php_dir = exec('brew --prefix php53')) {
                $this->addPath($php_dir . '/libexec/apache2');
            }
        }

        return $this;
    }

    /**
     * Replaces the entire module search path.
     *
     * @param array $paths
     *
     * @return self
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * Adds a new path to the module search path.
     *
     * @param string $path
     *
     * @return self
     */
    public function addPath($path)
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * Adds new paths to the module search path.
     *
     * @param array $paths
     *
     * @return self
     */
    public function addPaths(array $paths)
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }

        return $this;
    }

    /**
     * Finds a module by file name.
     *
     * @param  string $name The module's filename, e.g. libphp.so
     *
     * @return string The module path
     *
     * @throws \InvalidArgumentException When file is not found
     */
    public function find($name)
    {
        foreach (array_reverse($this->paths) as $path) {
            $file = $path.DIRECTORY_SEPARATOR.$name;
            if (file_exists($file)) {
                return $file;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('The file "%s" does not exist (in: %s).', $name, implode(', ', $this->paths))
        );
    }
}
