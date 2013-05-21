<?php

/*
 * This file is part of the Feather package.
 *
 * (c) Roger López <roger@zroger.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zroger\Feather\Tests\Config;

use Zroger\Feather\Config\ModuleFinder;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Yaml\Yaml;

class ModuleFinderTest extends \PHPUnit_Framework_TestCase
{
    private $root;

    public function setup()
    {
        $structure = Yaml::parse(__DIR__ . '/../fixtures/vfs-osx.yml');
        $this->root = vfsStream::setup('root', null, $structure);
    }

    public function testModuleIsFound()
    {
        $finder = new ModuleFinder(false);
        $finder->addPath($this->root->url() . '/usr/libexec/apache2');

        $found = $finder->find('libphp5.so');
        $this->assertEquals($found, $this->root->url() . '/usr/libexec/apache2/libphp5.so');
        $this->assertFileExists($found);
    }

    public function testPathsAreSearchedInReverseOrder()
    {
        $finder = new ModuleFinder(false);

        $path1 = $this->root->url() . '/usr/libexec/apache2';
        $finder->addPath($path1);

        $found = $finder->find('libphp5.so');
        $this->assertEquals($found, $path1 . '/libphp5.so');

        $path2 = $this->root->url() . '/usr/local/opt/php53/libexec/apache2';
        $finder->addPath($path2);

        $found = $finder->find('libphp5.so');
        $this->assertEquals($found, $path2 . '/libphp5.so');
    }

    /**
     * This test is intended to validate the default module paths, and help
     * identify systems and configurations that are not yet supported.
     */
    public function testDefaultModulePaths()
    {
        $finder = new ModuleFinder();
        $found = $finder->find('mod_dir.so');
        $this->assertTrue(!empty($found));
    }
}
