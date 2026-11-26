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

namespace Austral\AdminBundle\Tests\Unit\Page;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Page\ListUrlStore;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\Security\ModuleAccess;
use Austral\AdminBundle\Tests\Unit\ModuleDefinitions;
use Austral\AdminBundle\Variant\ModuleVariant;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ListUrlStoreTest extends TestCase
{
  public function testTheLastUrlOfTheListByModuleAndVariant(): void
  {
    $routes = new RouteCollection();
    $routes->add('austral_admin.module.pages.list', new Route('/admin/pages'));
    $store = new ListUrlStore(new AdminUrlGenerator(new UrlGenerator($routes, new RequestContext())));
    $module = new Module(ModuleDefinitions::entity(), new ModuleAccess($this->createStub(AuthorizationCheckerInterface::class)));
    $session = new Session(new MockArraySessionStorage());

    $request = Request::create('/admin/pages?page=3');
    $request->setSession($session);
    self::assertSame('/admin/pages', $store->get($module, $request), 'The list by default.');
    $store->save($module, $request);
    self::assertSame('/admin/pages?page=3', $store->get($module, $request));

    $store->save($module->withVariants([new ModuleVariant('news', 'News')]), Request::create('/admin/pages/news?page=2'));
    self::assertSame('/admin/pages?page=3', $store->get($module, $request), 'A request without session saves nothing.');
    $news = Request::create('/admin/pages/news?page=2');
    $news->setSession($session);
    $store->save($module->withVariants([new ModuleVariant('news', 'News')]), $news);
    self::assertSame('/admin/pages/news?page=2', $session->get('austral_admin.list_url.pages/news@'), 'A variant has its own URL.');
    self::assertSame('/admin/pages?page=3', $store->get($module, $request));
  }

  public function testAnUrlOfAnotherSiteIsNotUsed(): void
  {
    $routes = new RouteCollection();
    $routes->add('austral_admin.module.pages.list', new Route('/admin/pages'));
    $store = new ListUrlStore(new AdminUrlGenerator(new UrlGenerator($routes, new RequestContext())));
    $module = new Module(ModuleDefinitions::entity(), new ModuleAccess($this->createStub(AuthorizationCheckerInterface::class)));
    $session = new Session(new MockArraySessionStorage());
    $request = Request::create('/admin/pages');
    $request->setSession($session);

    foreach (['//evil.example', '/\\evil.example', 'https://evil.example'] as $url)
    {
      $session->set('austral_admin.list_url.pages@', $url);
      self::assertSame('/admin/pages', $store->get($module, $request), $url);
    }
  }
}
