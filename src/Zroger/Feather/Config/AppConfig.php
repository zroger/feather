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
        ->scalarNode('root')->end()
        ->scalarNode('template')->end()
        ->arrayNode('modules')
          ->useAttributeAsKey('module')
          ->prototype('scalar')
        ->end();

    return $treeBuilder;
  }
}
