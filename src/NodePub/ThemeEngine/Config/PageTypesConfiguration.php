<?php

namespace NodePub\ThemeEngine\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Configuration for mapping different page types to a theme template
 */
class PageTypesConfiguration implements ConfigurationInterface
{
    const DEFAULT_LAYOUT = 'layout';
    
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('page_types');
        
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('default')
                    ->defaultValue(self::DEFAULT_LAYOUT)
                ->end()
                ->scalarNode('homepage')
                    ->defaultValue(self::DEFAULT_LAYOUT)
                ->end()
                
                ->arrayNode('blog')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('index')
                            ->defaultValue('blog_index')
                        ->end()
                        ->scalarNode('post')
                            ->defaultValue('blog_post')
                        ->end()
                        ->scalarNode('tags')
                            ->defaultValue('blog_index')
                        ->end()
                        ->scalarNode('category')
                            ->defaultValue('blog_index')
                        ->end()
                        ->scalarNode('archive')
                            ->defaultValue('blog_archive')
                        ->end()
                    ->end()
                ->end()
                
                ->arrayNode('gallery')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('index')
                            ->defaultValue('gallery_index')
                        ->end()
                        ->scalarNode('item')
                            ->defaultValue('gallery_item')
                        ->end()
                    ->end()
                ->end()
                
                ->arrayNode('store')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('products')
                            ->defaultValue('store_products')
                        ->end()
                        ->scalarNode('product')
                            ->defaultValue('store_product')
                        ->end()
                        ->scalarNode('cart')
                            ->defaultValue('store_cart')
                        ->end()
                    ->end()
                ->end()
                
                ->arrayNode('error')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('general')
                            ->defaultValue('error')
                        ->end()
                        ->scalarNode('404')
                            ->defaultValue('error')
                        ->end()
                        ->scalarNode('500')
                            ->defaultValue('error')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}