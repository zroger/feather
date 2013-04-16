<?php

namespace Zroger\Feather\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class AppConfig implements ConfigurationInterface {
  public function getConfigTreeBuilder() {
    $treeBuilder = new TreeBuilder();
    $rootNode = $treeBuilder->root('app');

    $rootNode
      ->children()
        ->integerNode('port')
          ->defaultValue(8080)
          ->min(1)->max(65535)
        ->end()
        ->scalarNode('root')
          ->validate()
          ->ifNull()
            ->then(function() { return posix_getcwd(); })
          ->end()
        ->end()
        ->scalarNode('template')
          ->defaultValue('drupal.twig')
        ->end()
        ->enumNode('log_level')
          ->values(array('debug', 'info', 'notice', 'warn', 'error', 'crit', 'alert', 'emerg'))
        ->end()
        ->arrayNode('modules')
          ->useAttributeAsKey('module')
          ->prototype('scalar')
          ->defaultValue(array(
            "authz_host_module" => "mod_authz_host.so",
            "dir_module"        => "mod_dir.so",
            "env_module"        => "mod_env.so",
            "mime_module"       => "mod_mime.so",
            "log_config_module" => "mod_log_config.so",
            "rewrite_module"    => "mod_rewrite.so",
            "php5_module"       => "libphp5.so",
          ))
        ->end();

    return $treeBuilder;
  }
}
