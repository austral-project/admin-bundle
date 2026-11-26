<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Austral\AdminBundle\Tests\Unit\Routing;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleRegistry;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\Security\ModuleAccess;
use Austral\AdminBundle\Tests\Fixtures\Entity\Page;
use Austral\AdminBundle\Tests\Unit\ModuleDefinitions;
use Austral\AdminBundle\Variant\ModuleVariant;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Config\ContainerParametersResource;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AdminRoutingTest extends TestCase
{
  private ModuleRegistry $registry;

  protected function setUp(): void
  {
    $this->registry = new ModuleRegistry([
      'pages' => ModuleDefinitions::raw('pages', ['admin' => 'a', 'entity_manager' => 'em', 'truncate' => true, 'sortable' => 'position', 'download_formats' => ['csv'], 'extend_actions' => ['publish' => 'publish'], 'children' => ['pages/categories']]),
      'pages/categories' => ModuleDefinitions::raw('pages/categories', ['admin' => 'a', 'entity_manager' => 'em', 'disabled_actions' => ['duplicate']]),
      'articles' => ModuleDefinitions::raw('articles', ['admin' => 'a', 'entity_manager' => 'em', 'variant_providers' => ['domain', 'category']]),
      'tools' => ModuleDefinitions::raw('tools', ['admin' => 'a', 'actions' => ['index' => 'index', 'refresh' => 'refresh']]),
      'security' => ModuleDefinitions::raw('security'),
    ]);
  }

  /**
   * @param list<string> $languages
   */
  private function routes(array $languages = []): RouteCollection
  {
    return new AdminRouteLoader($this->registry, '/admin/', $languages, 'hash')->load('.', AdminRouteLoader::TYPE);
  }

  public function testTheRoutesOfTheModules(): void
  {
    $routes = $this->routes();
    $paths = [];
    foreach ($routes as $name => $route)
    {
      $paths[$name] = implode('|', $route->getMethods()).' '.$route->getPath();
    }

    self::assertSame('GET /admin/', $paths['austral_admin_index']);
    self::assertSame('GET /admin/pages', $paths['austral_admin.module.pages.list']);
    self::assertSame('POST /admin/pages/delete-all', $paths['austral_admin.module.pages.truncate']);
    self::assertSame('POST /admin/pages/sort', $paths['austral_admin.module.pages.sort']);
    self::assertSame('GET /admin/pages/export.{format}', $paths['austral_admin.module.pages.export']);
    self::assertSame('POST /admin/pages/{id}/toggle/{field}', $paths['austral_admin.module.pages.toggle']);
    self::assertSame('GET|POST /admin/pages/{id}/publish', $paths['austral_admin.module.pages.publish']);
    self::assertArrayNotHasKey('austral_admin.module.pages.categories.duplicate', $paths, 'A disabled action has no route.');
    self::assertArrayNotHasKey('austral_admin.module.pages.categories.truncate', $paths);
    self::assertSame('GET /admin/articles', $paths['austral_admin.module.articles.variants0']);
    self::assertSame('GET /admin/articles/{variant0}', $paths['austral_admin.module.articles.variants1']);
    self::assertSame('GET /admin/articles/{variant0}/{variant1}', $paths['austral_admin.module.articles.list']);
    self::assertSame('GET|POST /admin/articles/{variant0}/{variant1}/{id}/edit', $paths['austral_admin.module.articles.edit']);
    self::assertSame('GET|POST /admin/tools', $paths['austral_admin.module.tools.list']);
    self::assertSame('GET|POST /admin/tools/refresh', $paths['austral_admin.module.tools.refresh']);
    self::assertSame('GET /admin/security', $paths['austral_admin.module.security.list']);
    self::assertSame('csv', $routes->get('austral_admin.module.pages.export')?->getRequirement('format'));
    self::assertEquals([new ContainerParametersResource(['austral_admin.routes_hash' => 'hash'])], $routes->getResources());
  }

  public function testAChildWinsOverTheParametersOfItsParent(): void
  {
    $matcher = new UrlMatcher($this->routes(), new RequestContext());

    self::assertSame('austral_admin.module.pages.categories.list', $matcher->match('/admin/pages/categories')['_route']);
    self::assertSame('austral_admin.module.pages.edit', $matcher->match('/admin/pages/categories/edit')['_route']);
    self::assertSame('pages/categories', $matcher->match('/admin/pages/categories/12/edit')['_austral_admin_module']);
  }

  public function testTheLanguagesOfTheContent(): void
  {
    $routes = $this->routes(['fr', 'en']);
    $matcher = new UrlMatcher($routes, new RequestContext());

    self::assertSame('/admin/pages/{id}/edit/{language}', $routes->get('austral_admin.module.pages.edit')?->getPath());
    self::assertSame('/admin/pages/delete-all', $routes->get('austral_admin.module.pages.truncate')?->getPath());
    self::assertSame('fr', $matcher->match('/admin/pages/12/edit/fr')['language']);
    self::assertNull($matcher->match('/admin/pages/12/edit')['language']);
    self::assertSame('austral_admin.module.pages.categories.list', $matcher->match('/admin/pages/categories')['_route'], 'Not a language.');
  }

  public function testTheUrlsKeepTheVariantsAndTheLanguage(): void
  {
    $urls = new AdminUrlGenerator(new UrlGenerator($this->routes(['fr', 'en']), new RequestContext()));
    $access = new ModuleAccess($this->createStub(AuthorizationCheckerInterface::class));
    $articles = new Module($this->registry->get('articles'), $access);
    $page = new Page('Alpha');

    self::assertSame('/admin/articles', $urls->module($articles));
    $domain = $articles->withVariants([new ModuleVariant('main', 'Main')]);
    self::assertSame('/admin/articles/main', $urls->module($domain), 'The tiles of the next level.');
    $category = $articles->withVariants([new ModuleVariant('main', 'Main'), new ModuleVariant('news', 'News')])->withLanguage('en');
    self::assertSame('/admin/articles/main/news/en', $urls->module($category));
    self::assertSame("/admin/articles/main/news/{$page->getId()}/edit/en", $urls->object($category, 'edit', $page));
    self::assertSame('/admin/articles', $urls->definition($this->registry->get('articles')));

    $pages = new Module($this->registry->get('pages'), $access);
    self::assertSame("/admin/pages/{$page->getId()}/toggle/isPublished?value=1", $urls->toggle($pages, $page, 'isPublished', true));
    self::assertSame('/admin/pages/export.csv', $urls->module($pages, 'download', ['format' => 'csv']), 'The 3.1 name of export.');
  }
}
