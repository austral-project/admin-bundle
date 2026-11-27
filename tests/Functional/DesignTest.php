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
 * The pages of the admin use the build of the project (entry austral-admin, read from the entrypoints.json of the
 * option austral_admin.build) and the form theme of design-bundle. Strict Content Security Policy: an inline style or
 * script is only allowed with the nonce of the CSP (attribute nonce, managed by http-bundle), never without it.
 */
final class DesignTest extends AdminWebTestCase
{
  public function testTheLayoutLoadsTheBuildOfTheProject(): void
  {
    $this->login('root');

    $crawler = $this->client->request('GET', '/austral-admin/');

    self::assertResponseIsSuccessful();
    self::assertGreaterThan(0, $crawler->filter('link[rel="stylesheet"][href="/build/austral-admin.css"]')->count());
    self::assertGreaterThan(0, $crawler->filter('script[src="/build/austral-admin.js"][defer]')->count());
  }

  public function testThePagesHaveNoInlineStyleNorScript(): void
  {
    $this->login('root');
    $page = $this->page('Home');

    foreach (['/austral-admin/', '/austral-admin/pages', '/austral-admin/pages/new', "/austral-admin/pages/{$page->getId()}/edit", '/austral-admin/me'] as $url)
    {
      $crawler = $this->client->request('GET', $url);
      self::assertResponseIsSuccessful($url);
      self::assertSame(0, $crawler->filter('style:not([nonce])')->count(), "Inline style without the CSP nonce on {$url}.");
      self::assertSame(0, $crawler->filter('script:not([src]):not([nonce])')->count(), "Inline script without the CSP nonce on {$url}.");
      self::assertSame(0, $crawler->filter('[onclick], [onsubmit], [onchange]')->count(), "Inline event handler on {$url}.");
    }
  }

  public function testTheLoginPageHasNoInlineScript(): void
  {
    $crawler = $this->client->request('GET', '/austral-admin/login');

    self::assertResponseIsSuccessful();
    self::assertSame(0, $crawler->filter('style:not([nonce]), script:not([src]):not([nonce])')->count(), 'Inline style or script without the CSP nonce on the login page.');
  }

  public function testTheFormsToConfirmHaveTheTextsOfTheConfirmation(): void
  {
    $this->login('root');
    $page = $this->page('Home');

    $crawler = $this->client->request('GET', "/austral-admin/pages/{$page->getId()}/edit");

    $form = $crawler->filter('form[action$="/delete"][data-confirm]');
    self::assertSame(1, $form->count());
    self::assertSame('Delete this element?', $form->attr('data-confirm'));
    self::assertSame('Confirm', $form->attr('data-confirm-yes'));
    self::assertSame('Cancel', $form->attr('data-confirm-no'));
  }
}
