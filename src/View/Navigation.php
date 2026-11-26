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

use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Module\ModuleDefinition;
use Austral\AdminBundle\Module\ModuleRegistry;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\Security\ModuleAccess;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Austral Navigation.
 *
 * The menu of the admin: the modules in navigation that the user can open, by position, built when a template
 * asks for it (austral_admin_navigation()), once by request.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @phpstan-type NavigationItem array{key: string, path: string, label: string, picto: ?string, picto_tile: ?string, url: string, active: bool, children: list<mixed>}
 */
final class Navigation implements ResetInterface
{
  /**
   * @var list<array<string, mixed>>|null
   */
  private ?array $items = null;

  /**
   * @param ModuleRegistry    $registry
   * @param ModuleAccess      $access
   * @param AdminUrlGenerator $urls
   * @param RequestStack      $requestStack
   */
  public function __construct(
    private readonly ModuleRegistry $registry,
    private readonly ModuleAccess $access,
    private readonly AdminUrlGenerator $urls,
    private readonly RequestStack $requestStack,
  ) {
  }

  /**
   * @return list<array<string, mixed>> The dashboard, then the modules
   */
  public function build(): array
  {
    if (null === $this->items)
    {
      $current = $this->requestStack->getMainRequest()?->attributes->get(AdminRouteLoader::ATTRIBUTE_MODULE);
      $this->items = [[
        'key' => 'dashboard',
        'path' => '',
        'label' => 'breadcrumb.dashboard',
        'picto' => 'austral-picto-home',
        'picto_tile' => null,
        'url' => $this->urls->route(AdminRouteLoader::ROUTE_INDEX),
        'active' => AdminRouteLoader::ROUTE_INDEX === $this->requestStack->getMainRequest()?->attributes->get('_route'),
        'children' => [],
      ], ...$this->items($this->registry->roots(), \is_string($current) ? $current : null)];
    }

    return $this->items;
  }

  /**
   * @return void
   */
  public function reset(): void
  {
    $this->items = null;
  }

  /**
   * Children of a module that the user can open (tiles of a container module).
   *
   * @param ModuleDefinition $definition
   *
   * @return list<array<string, mixed>>
   */
  public function children(ModuleDefinition $definition): array
  {
    return $this->items($this->registry->children($definition), null, false);
  }

  /**
   * @param list<ModuleDefinition> $definitions
   * @param string|null            $current
   * @param bool                   $navigationOnly
   *
   * @return list<array<string, mixed>>
   */
  private function items(array $definitions, ?string $current, bool $navigationOnly = true): array
  {
    $items = [];
    foreach ($definitions as $definition)
    {
      if (($navigationOnly && !$definition->isNavigationEnabled()) || !$this->access->isGranted($definition, ModuleAction::LIST))
      {
        continue;
      }
      $children = $navigationOnly ? $this->items($this->registry->children($definition), $current) : [];
      $path = $definition->getPath();
      $items[] = [
        'key' => $definition->getKey(),
        'path' => $path,
        'label' => "pages.names.{$definition->getTranslationKey()}.plural",
        'picto' => $definition->getPicto(),
        'picto_tile' => $definition->getPictoTile(),
        'url' => $this->urls->definition($definition),
        'active' => null !== $current && ($current === $path || str_starts_with($current, $path.'/')),
        'children' => $children,
      ];
    }

    return $items;
  }
}
