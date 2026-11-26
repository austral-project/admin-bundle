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
 * The pages of the admin use the compiled build of design-bundle (entry austral-design-admin) and its form theme,
 * without inline style nor script (strict Content Security Policy).
 */
final class DesignTest extends AdminWebTestCase
{
  public function testTheLayoutLoadsTheBuildOfDesignBundle(): void
  {
    $this->login('root');

    $crawler = $this->client->request('GET', '/austral-admin/');

    self::assertResponseIsSuccessful();
    self::assertGreaterThan(0, $crawler->filter('link[rel="stylesheet"][href*="/bundles/australdesign/build/austral-design-admin."]')->count());
    self::assertGreaterThan(0, $crawler->filter('script[src*="/bundles/australdesign/build/austral-design-admin."][defer]')->count());
  }

  public function testThePagesHaveNoInlineStyleNorScript(): void
  {
    $this->login('root');
    $page = $this->page('Home');

    foreach (['/austral-admin/', '/austral-admin/pages', '/austral-admin/pages/new', "/austral-admin/pages/{$page->getId()}/edit", '/austral-admin/me'] as $url)
    {
      $crawler = $this->client->request('GET', $url);
      self::assertResponseIsSuccessful($url);
      self::assertSame(0, $crawler->filter('style')->count(), "Inline style on {$url}.");
      self::assertSame(0, $crawler->filter('script:not([src])')->count(), "Inline script on {$url}.");
      self::assertSame(0, $crawler->filter('[onclick], [onsubmit], [onchange]')->count(), "Inline event handler on {$url}.");
    }
  }

  public function testTheLoginPageHasNoInlineScript(): void
  {
    $crawler = $this->client->request('GET', '/austral-admin/login');

    self::assertResponseIsSuccessful();
    self::assertSame(0, $crawler->filter('style, script:not([src])')->count());
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
