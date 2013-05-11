<?php

namespace Zroger\Feather\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class AppConfig implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('app');

        $rootNode
            ->children()
                ->scalarNode('server_root')
                    // @codingStandardsIgnoreStart
                    ->info('The path to the server root.  Relative paths will be resolved based on the current working directory.')
                    // @codingStandardsIgnoreEnd
                    ->defaultValue('./.feather')
                ->end()
                ->integerNode('port')
                    ->info('The port number for apache to listen on.')
                    ->defaultValue(8080)
                    ->min(1)->max(65535)
                ->end()
                ->scalarNode('document_root')
                    // @codingStandardsIgnoreStart
                    ->info('The path to the document root.  Relative paths will be resolved to the current working directory.')
                    // @codingStandardsIgnoreEnd
                    ->defaultValue('./')
                ->end()
                ->scalarNode('template')
                    ->defaultValue('drupal.twig')
                ->end()
                ->enumNode('log_level')
                  ->defaultValue('info')
                  ->values(array('debug', 'info', 'notice', 'warn', 'error', 'crit', 'alert', 'emerg'))
                ->end()
                ->arrayNode('modules')
                    ->prototype('scalar')->end()
                    ->defaultValue(
                        array(
                            "authz_host_module" => "mod_authz_host.so",
                            "dir_module"        => "mod_dir.so",
                            "env_module"        => "mod_env.so",
                            "mime_module"       => "mod_mime.so",
                            "log_config_module" => "mod_log_config.so",
                            "rewrite_module"    => "mod_rewrite.so",
                            "php5_module"       => "libphp5.so",
                        )
                    )
                ->end();

        return $treeBuilder;
    }
}
