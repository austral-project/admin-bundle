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

namespace Austral\AdminBundle\Tests\Functional;

/**
 * The editor has the roles ROLE_PAGES (list) and ROLE_PAGES_CHANGE (toggle) only: the roles of the 3.1.
 */
final class RightsTest extends AdminWebTestCase
{
  protected static function editorRoles(): array
  {
    return ['ROLE_PAGES', 'ROLE_PAGES_CHANGE'];
  }

  public function testAnonymousGoesToTheLogin(): void
  {
    $this->client->request('GET', '/austral-admin/pages');
    self::assertResponseRedirects('/austral-admin/login');
  }

  public function testAUserWithoutAdminAccessIsDenied(): void
  {
    $this->client->loginUser($this->user('member', 'user'), 'austral_admin');
    $this->client->request('GET', '/austral-admin/');
    self::assertResponseStatusCodeSame(403);
  }

  public function testTheEditorSeesOnlyTheGrantedActions(): void
  {
    $page = $this->page('Alpha');
    $this->login('editor');

    $crawler = $this->client->request('GET', '/austral-admin/pages');
    self::assertResponseIsSuccessful();
    self::assertCount(0, $crawler->filter('a[href="/austral-admin/pages/new"]'));
    self::assertCount(0, $crawler->filter('form[action$="/delete"]'));
    self::assertCount(0, $crawler->filter('a[href$="/edit"]'));
    self::assertCount(1, $crawler->filter('form[action*="/toggle/isPublished"]'), 'ROLE_PAGES_CHANGE gives the switches.');
    self::assertNull($crawler->filter('tbody tr')->first()->attr('data-url'), 'No edit link on the row.');

    $token = $this->csrfToken();
    $this->client->request('GET', '/austral-admin/pages/new');
    self::assertResponseStatusCodeSame(403);
    $this->client->request('GET', "/austral-admin/pages/{$page->getId()}/edit");
    self::assertResponseStatusCodeSame(403);
    $this->client->request('POST', "/austral-admin/pages/{$page->getId()}/delete", ['_token' => $token]);
    self::assertResponseStatusCodeSame(403);
    $this->client->request('GET', '/austral-admin/pages/export.csv');
    self::assertResponseStatusCodeSame(403);
    $this->client->request('POST', "/austral-admin/pages/{$page->getId()}/toggle/isPublished?value=1", ['_token' => $token]);
    self::assertResponseRedirects();
    self::assertTrue($this->reload($page)?->getIsPublished());
  }

  public function testTheEditorDoesNotOpenTheOtherModules(): void
  {
    $this->login('editor');

    $crawler = $this->client->request('GET', '/austral-admin/');
    self::assertResponseIsSuccessful();
    $navigation = $crawler->filter('.austral-admin-navigation')->text();
    self::assertStringContainsString('pages', $crawler->filter('.austral-admin-navigation a[href="/austral-admin/pages"]')->attr('href') ?? '');
    self::assertCount(0, $crawler->filter('.austral-admin-navigation a[href="/austral-admin/articles"]'));
    self::assertCount(0, $crawler->filter('.austral-admin-navigation a[href="/austral-admin/library"]'));
    self::assertNotSame('', $navigation);

    foreach (['/austral-admin/articles', '/austral-admin/articles/news', '/austral-admin/library', '/austral-admin/library/books', '/austral-admin/report', '/austral-admin/status-configuration'] as $url)
    {
      $this->client->request('GET', $url);
      self::assertResponseStatusCodeSame(403, $url);
    }
  }

  public function testRootOpensEverything(): void
  {
    $this->login('root');

    foreach (['/austral-admin/', '/austral-admin/pages', '/austral-admin/articles', '/austral-admin/library', '/austral-admin/library/books', '/austral-admin/report', '/austral-admin/status-configuration', '/austral-admin/me'] as $url)
    {
      $this->client->request('GET', $url);
      self::assertResponseIsSuccessful($url);
    }
  }
}
