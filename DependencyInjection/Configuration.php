<?php

namespace Incolab\BlogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('incolab_blog');
        
        $rootNode
            ->children()
                ->arrayNode('news')
                    ->children()
                        ->integerNode('nb_char_index')->defaultValue(500)->end()
                        ->integerNode('news_by_page')->defaultValue(6)->end()
                    ->end()
                ->end()
            ->end()
            ;
            
        return $treeBuilder;
    }
}