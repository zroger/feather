<?php

namespace Zroger\Feather\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

use Zroger\Feather\Config\AppConfig;

class FeatherExtension implements ExtensionInterface
{
    protected $basePath;

    /**
     * Initializes configuration.
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Set module lookup path defaults dynamically.
        $container->setParameter('feather.paths.modules', $this->getModuleDirectories());

        $processor = new Processor();
        $configuration = new AppConfig();
        $config = $processor->processConfiguration($configuration, $configs);

        foreach ($config as $key => $value) {
            $container->setParameter(join('.', array($this->getAlias(), $key)), $value);
        }

        // Resolve module filenames to full paths.
        $modules = array();
        foreach ($config['modules'] as $mod => $filename) {
            $locator = new FileLocator($container->getParameter('feather.paths.modules'));
            $modules[$mod] = $locator->locate($filename, null, true);
        }
        $container->setParameter('feather.modules', $modules);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'feather';
    }

    /**
     * {@inheritdoc}
     */
    public function getXsdValidationBasePath()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return 'http://example.com/schema';
    }

    /**
     * Get the list of potential module directories, ordered from most preferred
     * to least.
     *
     * @return array Sorted array of possible module directories.
     */
    protected function getModuleDirectories()
    {
        if (!isset($this->moduleDirectories)) {
            $dirs = array();

            // PHP 5.3 from josegonzalez/php/php53 homebrew tap.
            if (exec('which brew')) {
                if ($php_dir = exec('brew --prefix php53')) {
                    $dirs[] = $php_dir . '/libexec/apache2';
                }
            }

            // CentOS
            $dirs[] = '/usr/lib64/httpd/modules/';

            // Ubuntu
            $dirs[] = '/usr/lib/apache2/modules';

            // osx default apache.
            $dirs[] = '/usr/libexec/apache2';

            $this->moduleDirectories = $dirs;
        }
        return $this->moduleDirectories;
    }
}
