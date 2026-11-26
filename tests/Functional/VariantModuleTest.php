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

use Austral\AdminBundle\Tests\Fixtures\Entity\Page;

/**
 * The module "articles": a variant by category (CategoryVariantProvider), "duplicate" and "change" disabled.
 */
final class VariantModuleTest extends AdminWebTestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    $this->login('root');
  }

  public function testTheModuleShowsTheTilesOfTheVariants(): void
  {
    $crawler = $this->client->request('GET', '/austral-admin/articles');

    self::assertResponseIsSuccessful();
    self::assertCount(1, $crawler->filter('a[href="/austral-admin/articles/news"]'));
    self::assertCount(1, $crawler->filter('a[href="/austral-admin/articles/events"]'));
  }

  public function testTheListOfAVariantIsRestricted(): void
  {
    $this->page('A news');
    $this->page('An event', 'events');

    $crawler = $this->client->request('GET', '/austral-admin/articles/events');
    self::assertResponseIsSuccessful();
    self::assertCount(1, $crawler->filter('tbody tr'));
    self::assertStringContainsString('An event', $crawler->filter('tbody')->text());
    self::assertStringContainsString('Events', $crawler->filter('.austral-admin-breadcrumb')->text());
    self::assertCount(1, $crawler->filter('a[href="/austral-admin/articles/events/new"]'), 'The URLs keep the variant.');
  }

  public function testAnUnknownVariantIsNotFound(): void
  {
    $this->client->request('GET', '/austral-admin/articles/unknown');
    self::assertResponseStatusCodeSame(404);
  }

  public function testAnEntityOfAnotherVariantIsNotFound(): void
  {
    $news = $this->page('A news');

    $this->client->request('GET', "/austral-admin/articles/events/{$news->getId()}/edit");
    self::assertResponseStatusCodeSame(404);
    $this->client->request('GET', "/austral-admin/articles/news/{$news->getId()}/edit");
    self::assertResponseIsSuccessful();
  }

  public function testCreateInAVariant(): void
  {
    $crawler = $this->client->request('GET', '/austral-admin/articles/events/new');
    self::assertResponseIsSuccessful();
    self::assertSame('events', $crawler->filter('input[name="form[category]"]')->attr('value'), 'The variant prepares the new entity.');

    $this->client->submit($crawler->filter('form[name="form"]')->form(['form[name]' => 'Created']));
    $page = $this->doctrine()->getRepository(Page::class)->findOneBy(['name' => 'Created']);
    self::assertInstanceOf(Page::class, $page);
    self::assertResponseRedirects("/austral-admin/articles/events/{$page->getId()}/edit");
  }

  public function testDisabledActionsHaveNoRoute(): void
  {
    $news = $this->page('A news');
    $token = $this->csrfToken();

    $this->client->request('POST', "/austral-admin/articles/news/{$news->getId()}/duplicate", ['_token' => $token]);
    self::assertResponseStatusCodeSame(404);
    $this->client->request('POST', "/austral-admin/articles/news/{$news->getId()}/toggle/isPublished?value=1", ['_token' => $token]);
    self::assertResponseStatusCodeSame(404);
    $crawler = $this->client->request('GET', '/austral-admin/articles/news');
    self::assertCount(0, $crawler->filter('form[action$="/duplicate"]'));
    self::assertCount(0, $crawler->filter('form[action*="/toggle/"]'), 'The switches are not changed from the list.');
  }
}
