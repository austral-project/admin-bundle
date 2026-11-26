<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Austral Admin Configuration.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class Configuration implements ConfigurationInterface
{
  /**
   * Levels of children of a module.
   */
  public const int MAX_DEPTH = 4;

  /**
   * @return TreeBuilder
   */
  public function getConfigTreeBuilder(): TreeBuilder
  {
    $treeBuilder = new TreeBuilder('austral_admin');
    $treeBuilder->getRootNode()
      ->addDefaultsIfNotSet()
      ->children()
        ->scalarNode('path')->cannotBeEmpty()->defaultValue('/austral-admin')->info('Prefix of the URLs of the admin')->end()
        ->scalarNode('name')->cannotBeEmpty()->defaultValue('Austral')->info('Name of the project in the admin')->end()
        ->arrayNode('languages')
          ->addDefaultsIfNotSet()
          ->children()
            ->arrayNode('interface')
              ->info('Languages of the interface, the first one by default')
              ->performNoDeepMerging()
              ->scalarPrototype()->end()
              ->defaultValue(['en', 'fr'])
              ->requiresAtLeastOneElement()
            ->end()
            ->arrayNode('content')
              ->info('Languages of the content (translated entities): the URLs of the forms end with the language')
              ->performNoDeepMerging()
              ->scalarPrototype()->end()
              ->defaultValue([])
            ->end()
          ->end()
        ->end()
        ->arrayNode('list')
          ->addDefaultsIfNotSet()
          ->children()
            ->integerNode('per_page')->min(0)->defaultValue(30)->info('Entities by page, 0 for no pagination')->end()
          ->end()
        ->end()
        ->arrayNode('export')
          ->addDefaultsIfNotSet()
          ->children()
            ->scalarNode('csv_delimiter')->defaultValue(',')->end()
          ->end()
        ->end()
        ->arrayNode('mail')
          ->addDefaultsIfNotSet()
          ->info('Sender of the password reset and login link mails (symfony/mailer), no mail when null')
          ->children()
            ->scalarNode('from')->defaultNull()->end()
            ->scalarNode('from_name')->defaultNull()->end()
          ->end()
        ->end()
        ->arrayNode('security')
          ->addDefaultsIfNotSet()
          ->children()
            ->booleanNode('password_reset')->defaultFalse()->info('Pages "password forgotten" and "reset", the mails need "mail.from"')->end()
          ->end()
        ->end()
        ->append($this->modulesNode('modules', 0))
      ->end();

    return $treeBuilder;
  }

  /**
   * @param string $name
   * @param int    $depth
   *
   * @return NodeDefinition
   */
  private function modulesNode(string $name, int $depth): NodeDefinition
  {
    $node = new ArrayNodeDefinition($name);
    $node->info(0 === $depth ? 'Modules of the admin, by key (a bundle adds its own with prepend())' : 'Children of the module');
    $node->useAttributeAsKey('key')->normalizeKeys(false);

    $module = $node->arrayPrototype();
    $module
      ->children()
        ->booleanNode('enabled')->defaultTrue()->end()
        ->scalarNode('name')->defaultNull()->info('Name of the module (the translations are pages.names.<path in camel case>)')->end()
        ->scalarNode('picto')->defaultNull()->end()
        ->scalarNode('pictoTile')->defaultNull()->end()
        ->scalarNode('route')->defaultNull()->info('Segment of the URL, the key by default')->end()
        ->scalarNode('class')->defaultNull()->info('Admin class (service id)')->end()
        ->scalarNode('entity_manager')->defaultNull()->info('Service id of the entity manager of an entity module')->end()
        ->scalarNode('entity')->defaultNull()->info('Entity class, when the entity manager does not give it')->end()
        ->arrayNode('navigation')
          ->addDefaultsIfNotSet()
          ->children()
            ->booleanNode('enabled')->defaultTrue()->end()
            ->integerNode('position')->defaultValue(0)->end()
          ->end()
        ->end()
        ->scalarNode('translate')->defaultNull()->info('Gender of the translations (male_v, female_c...)')->end()
        ->scalarNode('sortable')->defaultFalse()->info('true (field "position") or the position field')->end()
        ->booleanNode('truncate')->defaultFalse()->info('Action "delete all"')->end()
        ->arrayNode('actions')
          ->info('Page module: action => method of the Admin, "index" for the module')
          ->useAttributeAsKey('name')
          ->normalizeKeys(false)
          ->scalarPrototype()->end()
        ->end()
        ->arrayNode('extendActions')
          ->info('Entity module: action on an entity => method of the Admin')
          ->useAttributeAsKey('name')
          ->normalizeKeys(false)
          ->scalarPrototype()->end()
        ->end()
        ->arrayNode('disabledActions')
          ->performNoDeepMerging()
          ->scalarPrototype()->end()
        ->end()
        ->arrayNode('downloadFormats')
          ->info('Export formats: csv, json, xlsx (phpoffice/phpspreadsheet)')
          ->performNoDeepMerging()
          ->scalarPrototype()->end()
        ->end()
        ->scalarNode('security_key')->defaultNull()->info('Base of the roles, the path by default')->end()
      ->end();
    if ($depth < self::MAX_DEPTH)
    {
      $module->children()->append($this->modulesNode('children', $depth + 1))->end();
    }

    return $node;
  }
}
