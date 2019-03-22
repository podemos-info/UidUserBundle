<?php

namespace L3\Bundle\UidUserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('uid');

        $rootNode
            ->children()
            ->scalarNode('user_provider_class')->defaultValue("L3\Bundle\UidUserBundle\Security\Provider\UidUserProvider")->end()
            ->scalarNode('user_directory_class')->defaultValue('L3\Bundle\UidUserBundle\Entity\UidDirectory')->end()
            ->scalarNode('directory_app_id')->end()
            ->scalarNode('directory_api_server')->end()
            ->scalarNode('directory_api_version')->end()
            ->scalarNode('directory_system_user')->end()
            ->scalarNode('directory_object')->end()
            ->end();
        
        return $treeBuilder;
    }
}