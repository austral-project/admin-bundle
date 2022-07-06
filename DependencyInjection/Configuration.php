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
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Austral Admin Configuration.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Configuration implements ConfigurationInterface
{

  /**
   * {@inheritdoc}
   */
  public function getConfigTreeBuilder(): TreeBuilder
  {
    $treeBuilder = new TreeBuilder('austral_admin');

    $rootNode = $treeBuilder->getRootNode();
    $node = $rootNode->children();
    $node->scalarNode("default_class")->end();
    $node->booleanNode("compression_gzip")->end();
    $node->arrayNode("language")
        ->children()
          ->booleanNode("enabled_multi")->end()
          ->arrayNode("list")
            ->scalarPrototype()->end()
          ->end()
          ->arrayNode("user")
            ->scalarPrototype()->end()
          ->end()
        ->end()
      ->end();
    $node->arrayNode("project")
        ->children()
          ->scalarNode("name")->end()
          ->scalarNode("background")->end()
          ->booleanNode("forgot_enabled")->end()
          ->scalarNode("copyright")->end()
        ->end()
      ->end();
    $node->arrayNode("download")
        ->children()
          ->scalarNode("creator")->end()
          ->scalarNode("title_defore")->end()
          ->scalarNode("trans_domain")->end()
          ->scalarNode("encode")->end()
          ->arrayNode("xlsTheme")
            ->children()
              ->arrayNode("header")
                ->children()
                  ->scalarNode("color")->end()
                  ->scalarNode("background")->end()
                ->end()
              ->end()
              ->arrayNode("content")
                ->children()
                  ->scalarNode("color")->end()
                  ->scalarNode("background")->end()
                ->end()
              ->end()
            ->end()
          ->end()
        ->end()
      ->end();
    $node = $this->buildActionsNode($node
      ->arrayNode('actions_entities')
      ->arrayPrototype()
    );
    $node = $this->buildDataReloadJsNode($node
      ->arrayNode('data_reload_js')
      ->arrayPrototype()
    );

    $node = $this->buildModulesNode($node
      ->arrayNode('modules')
      ->arrayPrototype()
    );
    $node->end()->end()->end();
    return $treeBuilder;
  }

  /**
   * @param ArrayNodeDefinition $node
   *
   * @return mixed
   */
  protected function buildActionsNode(ArrayNodeDefinition $node)
  {
    $node = $node
      ->children()
        ->scalarNode('default')->isRequired()->cannotBeEmpty()->end()
        ->scalarNode('language')->end();
    return $node->end()->end()->end();
  }

  /**
   * @param ArrayNodeDefinition $node
   *
   * @return mixed
   */
  protected function buildDataReloadJsNode(ArrayNodeDefinition $node)
  {
    return $node->scalarPrototype()->end()->end()->end();
  }

  /**
   * @param ArrayNodeDefinition $node
   * @param int $withChildren
   *
   * @return mixed
   */
  protected function buildModulesNode(ArrayNodeDefinition $node, int $withChildren = 1)
  {
    $node = $node
      ->children()
        ->scalarNode('enabled')->isRequired()->end()
        ->booleanNode('truncate')->end()
        ->arrayNode('navigation')
          ->children()
            ->booleanNode('enabled')->end()
            ->integerNode('position')->end()
          ->end()
        ->end()
        ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
        ->scalarNode('picto')->isRequired()->cannotBeEmpty()->end()
        ->scalarNode('pictoTile')->end()
        ->scalarNode('class')->end()
        ->scalarNode("entity_manager")->end()
        ->scalarNode("data_hydrate_class")->end()
        ->arrayNode('actions')
          ->scalarPrototype()->end()
        ->end()
        ->booleanNode('sortable')->defaultFalse()->end()
        ->arrayNode('disabledActions')
          ->scalarPrototype()->end()
        ->end()
        ->arrayNode('extendActions')
          ->scalarPrototype()->end()
        ->end()
        ->scalarNode('route')->isRequired()->cannotBeEmpty()->end()
        ->scalarNode('translate')
          ->isRequired()
          ->cannotBeEmpty()
          ->validate()
            ->ifNotInArray(['female_c', 'female_v', 'male_c', 'male_v'])
            ->thenInvalid('Invalid genre to translate %s')
          ->end()
        ->end();
    $withChildren++;
    if($withChildren < 4)
    {
      $node = $this->buildModulesNode($node
        ->arrayNode('children')
        ->arrayPrototype(), $withChildren
      );
      $node->end()->end()->end();
    }
    return $node->end();
  }

}
