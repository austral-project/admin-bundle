<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Routing;

use Austral\AdminBundle\Controller\AdminController;
use Austral\AdminBundle\Controller\SecurityController;
use Austral\AdminBundle\DependencyInjection\Compiler\ModuleRegistryPass;
use Austral\AdminBundle\Event\AdminHttpEvent;
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Module\ModuleDefinition;
use Austral\AdminBundle\Module\ModuleRegistry;
use Austral\AdminBundle\Security\ModuleAccess;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Config\ContainerParametersResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Austral Admin Route Loader.
 *
 * Routes of the admin, loaded by the application (config/routes/austral_admin.yaml):
 *
 *   austral_admin:
 *     resource: .
 *     type: austral_admin
 *
 * The pages of the admin (index, login, account...) and, for each module, one route by available action. The
 * prefix is "austral_admin.path". Every route has the AdminHttpEvent (_austral_http_event): http-bundle dispatches
 * its events, and HttpAdminEventSubscriber prepares the AdminHandler of the controller.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminRouteLoader extends Loader
{
  public const string TYPE = 'austral_admin';

  public const string ATTRIBUTE_ADMIN = '_austral_admin';
  public const string ATTRIBUTE_MODULE = '_austral_admin_module';
  public const string ATTRIBUTE_ACTION = '_austral_admin_action';
  public const string ATTRIBUTE_HTTP_EVENT = '_austral_http_event';

  public const string ROUTE_INDEX = 'austral_admin_index';
  public const string ROUTE_LOGIN = 'austral_admin_security_login';
  public const string ROUTE_LOGOUT = 'austral_admin_security_logout';
  public const string ROUTE_LOGIN_LINK = 'austral_admin_security_login_link';
  public const string ROUTE_LOGIN_LINK_CHECK = 'austral_admin_security_login_link_check';
  public const string ROUTE_PASSWORD_FORGOT = 'austral_admin_security_password_forgot';
  public const string ROUTE_PASSWORD_RESET = 'austral_admin_security_password_reset';
  public const string ROUTE_ACCOUNT = 'austral_admin_my_account';

  /**
   * __construct
   *
   * @param ModuleRegistry $registry
   * @param string         $path       Prefix of the URLs (/austral-admin)
   * @param list<string>   $languages  Languages of the content, an optional segment of the URLs when not empty
   * @param string|null    $routesHash Hash of the modules (ModuleRegistryPass): the cache of the routes is refreshed when it changes
   * @param string|null    $env
   *
   * @return void
   */
  public function __construct(
    private readonly ModuleRegistry $registry,
    private readonly string $path,
    private readonly array $languages = [],
    private readonly ?string $routesHash = null,
    ?string $env = null,
  ) {
    parent::__construct($env);
  }

  /**
   * supports
   *
   * @param mixed       $resource
   * @param string|null $type
   *
   * @return bool
   */
  public function supports(mixed $resource, ?string $type = null): bool
  {
    return self::TYPE === $type;
  }

  /**
   * load
   *
   * @param mixed       $resource
   * @param string|null $type
   *
   * @return RouteCollection
   */
  public function load(mixed $resource, ?string $type = null): RouteCollection
  {
    $routes = new RouteCollection();
    if (null !== $this->routesHash)
    {
      $routes->addResource(new ContainerParametersResource([ModuleRegistryPass::PARAMETER_ROUTES_HASH => $this->routesHash]));
    }
    $prefix = '/'.trim($this->path, '/');
    $pages = [
      self::ROUTE_INDEX => ['/', AdminController::class.'::index', ['GET']],
      self::ROUTE_LOGIN => ['/login', SecurityController::class.'::login', ['GET', 'POST']],
      self::ROUTE_LOGOUT => ['/logout', SecurityController::class.'::logout', ['GET', 'POST']],
      self::ROUTE_LOGIN_LINK => ['/login/link', SecurityController::class.'::loginLink', ['GET', 'POST']],
      self::ROUTE_LOGIN_LINK_CHECK => ['/login/link/check', SecurityController::class.'::loginLinkCheck', ['GET', 'POST']],
      self::ROUTE_PASSWORD_FORGOT => ['/password/forgot', SecurityController::class.'::passwordForgot', ['GET', 'POST']],
      self::ROUTE_PASSWORD_RESET => ['/password/reset/{token}', SecurityController::class.'::passwordReset', ['GET', 'POST']],
      self::ROUTE_ACCOUNT => ['/me', AdminController::class.'::account', ['GET', 'POST']],
    ];
    foreach ($pages as $name => [$path, $controller, $methods])
    {
      $route = new Route($prefix.$path, ['_controller' => $controller, self::ATTRIBUTE_ADMIN => true, self::ATTRIBUTE_HTTP_EVENT => AdminHttpEvent::class], methods: $methods);
      if (self::ROUTE_PASSWORD_RESET === $name)
      {
        $route->setRequirement('token', '[0-9a-f]+');
      }
      $routes->add($name, $route);
    }

    // The children first: their literal segments win over the parameters of their parent.
    foreach (array_reverse($this->registry->all()) as $definition)
    {
      foreach ($this->moduleRoutes($definition, $prefix) as $name => $route)
      {
        $routes->add($name, $route);
      }
    }

    return $routes;
  }

  /**
   * moduleRoutes
   *
   * @param ModuleDefinition $definition
   * @param string           $prefix
   *
   * @return array<string, Route>
   */
  private function moduleRoutes(ModuleDefinition $definition, string $prefix): array
  {
    $base = $prefix.'/'.$definition->getPath();
    $routes = [];
    if ($definition->isContainer())
    {
      $routes[ModuleAction::LIST] = $this->route($definition, ModuleAction::LIST, $base, 'container', ['GET']);
    }
    elseif ($definition->isPage())
    {
      $routes[ModuleAction::LIST] = $this->route($definition, ModuleDefinition::PAGE_INDEX, $base, 'page', ['GET', 'POST']);
      foreach (array_keys($definition->getActions()) as $action)
      {
        if (ModuleDefinition::PAGE_INDEX !== $action && ModuleAccess::isAvailable($definition, $action))
        {
          $routes[$action] = $this->route($definition, $action, "{$base}/{$action}", 'page', ['GET', 'POST']);
        }
      }
    }
    else
    {
      $variants = '';
      for ($level = 0; $level < $definition->getVariantLevels(); ++$level)
      {
        $routes[AdminUrlGenerator::VARIANTS_ROUTE.$level] = $this->route($definition, AdminUrlGenerator::VARIANTS_ROUTE.$level, $base.$variants, 'variants', ['GET']);
        $variants .= '/{'.AdminUrlGenerator::variantParameter($level).'}';
      }
      $base .= $variants;
      $actions = [
        ModuleAction::LIST => ['', 'list', ['GET']],
        ModuleAction::CREATE => ['/new', 'create', ['GET', 'POST']],
        ModuleAction::TRUNCATE => ['/delete-all', 'truncate', ['POST']],
        ModuleAction::SORT => ['/sort', 'sort', ['POST']],
        ModuleAction::EXPORT => ['/export.{format}', 'export', ['GET']],
        ModuleAction::EDIT => ['/{id}/edit', 'edit', ['GET', 'POST']],
        ModuleAction::DUPLICATE => ['/{id}/duplicate', 'duplicate', ['POST']],
        ModuleAction::DELETE => ['/{id}/delete', 'delete', ['POST']],
        ModuleAction::TOGGLE => ['/{id}/toggle/{field}', 'toggle', ['POST']],
      ];
      foreach ($actions as $action => [$path, $method, $methods])
      {
        if (!ModuleAccess::isAvailable($definition, $action))
        {
          continue;
        }
        $route = $this->route($definition, $action, $base.$path, $method, $methods);
        if (ModuleAction::EXPORT === $action)
        {
          $route->setRequirement('format', implode('|', array_map(static fn (string $format): string => preg_quote($format, '#'), $definition->getDownloadFormats())));
        }
        if (ModuleAction::TOGGLE === $action)
        {
          $route->setRequirement('field', '[A-Za-z_][A-Za-z0-9_.]*');
        }
        $routes[$action] = $route;
      }
      foreach (array_keys($definition->getExtendActions()) as $action)
      {
        if (ModuleAccess::isAvailable($definition, $action))
        {
          $routes[$action] = $this->route($definition, $action, "{$base}/{id}/{$action}", 'action', ['GET', 'POST']);
        }
      }
    }

    $named = [];
    foreach ($routes as $action => $route)
    {
      $named[AdminUrlGenerator::routeName($definition->getPath(), (string) $action)] = $route;
    }

    return $named;
  }

  /**
   * route
   *
   * @param ModuleDefinition $definition
   * @param string           $action
   * @param string           $path
   * @param string           $method     Method of AdminController
   * @param list<string>     $methods
   *
   * @return Route
   */
  private function route(ModuleDefinition $definition, string $action, string $path, string $method, array $methods): Route
  {
    $defaults = [
      '_controller' => AdminController::class.'::'.$method,
      self::ATTRIBUTE_ADMIN => true,
      self::ATTRIBUTE_HTTP_EVENT => AdminHttpEvent::class,
      self::ATTRIBUTE_MODULE => $definition->getPath(),
      self::ATTRIBUTE_ACTION => $action,
    ];
    $requirements = ['id' => '[^/]+'];
    for ($level = 0; $level < $definition->getVariantLevels(); ++$level)
    {
      $requirements[AdminUrlGenerator::variantParameter($level)] = '[A-Za-z0-9][A-Za-z0-9_-]*';
    }
    if ($this->languages && \in_array($action, ModuleAction::WITH_LANGUAGE, true) && $definition->isEntity())
    {
      $path .= '/{language}';
      $defaults['language'] = null;
      $requirements['language'] = implode('|', array_map(static fn (string $language): string => preg_quote($language, '#'), $this->languages));
    }

    return new Route($path, $defaults, $requirements, methods: $methods);
  }
}
