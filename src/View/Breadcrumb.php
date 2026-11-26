<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\View;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Module\ModuleRegistry;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\Security\ModuleAccess;

/**
 * Austral Breadcrumb.
 *
 * Items: the dashboard, the parents of the module, the module, its variants and the current entity.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @phpstan-type BreadcrumbItem array{label: string, translate: bool, parameters: array<string, string>, url: ?string}
 */
final class Breadcrumb
{
  /**
   * @param ModuleRegistry    $registry
   * @param ModuleAccess      $access
   * @param AdminUrlGenerator $urls
   */
  public function __construct(
    private readonly ModuleRegistry $registry,
    private readonly ModuleAccess $access,
    private readonly AdminUrlGenerator $urls,
  ) {
  }

  /**
   * @param Module|null $module
   * @param string|null $last   Label of the last item (an entity), not translated
   *
   * @return list<array<string, mixed>>
   *
   * @phpstan-return list<BreadcrumbItem>
   */
  public function build(?Module $module = null, ?string $last = null): array
  {
    $items = [self::item('breadcrumb.dashboard', true, $this->urls->route(AdminRouteLoader::ROUTE_INDEX))];
    if (null !== $module)
    {
      $definition = $module->getDefinition();
      foreach ($this->registry->ancestors($definition) as $ancestor)
      {
        $items[] = self::item("pages.names.{$ancestor->getTranslationKey()}.plural", true, $this->access->isGranted($ancestor, ModuleAction::LIST) ? $this->urls->definition($ancestor) : null);
      }
      $items[] = self::item("pages.names.{$definition->getTranslationKey()}.plural", true, $this->urls->definition($definition));
      $chosen = [];
      foreach ($module->getVariants() as $variant)
      {
        $chosen[] = $variant;
        $items[] = self::item($variant->name, false, $this->urls->module($module->withVariants($chosen)));
      }
    }
    if (null !== $last)
    {
      $items[] = self::item($last, false, null);
    }
    // The current page is not a link.
    $current = array_pop($items);
    $items[] = self::item($current['label'], $current['translate'], null);

    return $items;
  }

  /**
   * @param string      $label
   * @param bool        $translate
   * @param string|null $url
   *
   * @return array<string, mixed>
   *
   * @phpstan-return BreadcrumbItem
   */
  private static function item(string $label, bool $translate, ?string $url): array
  {
    return ['label' => $label, 'translate' => $translate, 'parameters' => [], 'url' => $url];
  }
}
