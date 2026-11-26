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
 * The actions of an entity module, as root: list, form, delete, duplicate, toggle, sort, truncate, export.
 */
final class EntityModuleTest extends AdminWebTestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    $this->login('root');
  }

  public function testListShowsTheEntitiesOfTheModuleByPage(): void
  {
    $this->page('Alpha', position: 1);
    $this->page('Beta', position: 2);
    $this->page('Gamma', position: 3);
    $this->page('Hidden', 'secret', 0);

    $crawler = $this->client->request('GET', '/austral-admin/pages');
    self::assertResponseIsSuccessful();
    $rows = $crawler->filter('tbody tr');
    self::assertCount(2, $rows, 'Two entities by page (PageAdmin::setPerPage).');
    self::assertStringContainsString('Alpha', $rows->eq(0)->text());
    self::assertStringContainsString('Beta', $rows->eq(1)->text());
    self::assertStringNotContainsString('Hidden', $crawler->filter('main')->text(), 'The restriction of configureQuery() applies to the list.');
    self::assertCount(1, $crawler->filter('nav.austral-list-pagination a[rel="next"]'));

    $crawler = $this->client->request('GET', '/austral-admin/pages?page=2');
    self::assertCount(1, $crawler->filter('tbody tr'));
    self::assertStringContainsString('Gamma', $crawler->filter('tbody')->text());

    $crawler = $this->client->request('GET', '/austral-admin/pages?page=99');
    self::assertResponseIsSuccessful();
    self::assertStringContainsString('Gamma', $crawler->filter('tbody')->text(), 'A page after the last one shows the last one.');
  }

  public function testListFilters(): void
  {
    $this->page('Alpha');
    $this->page('Beta');

    $crawler = $this->client->request('GET', '/austral-admin/pages?filters[default][search]=alp');
    self::assertResponseIsSuccessful();
    self::assertCount(1, $crawler->filter('tbody tr'));
    self::assertStringContainsString('Alpha', $crawler->filter('tbody')->text());
  }

  public function testListRendersThePostActionsAsForms(): void
  {
    $this->page('Alpha');
    $crawler = $this->client->request('GET', '/austral-admin/pages');

    self::assertCount(1, $crawler->filter('form[action$="/delete"][method="post"] input[name="_token"]'));
    self::assertCount(1, $crawler->filter('form[action$="/duplicate"][method="post"]'));
    self::assertCount(1, $crawler->filter('form[action="/austral-admin/pages/delete-all"][method="post"]'));
    self::assertCount(0, $crawler->filter('a[href$="/delete"]'), 'No link changes data.');
    self::assertCount(1, $crawler->filter('a[href="/austral-admin/pages/new"]'));
    self::assertCount(3, $crawler->filter('a[href^="/austral-admin/pages/export."]'));
  }

  public function testCreate(): void
  {
    $crawler = $this->client->request('GET', '/austral-admin/pages/new');
    self::assertResponseIsSuccessful();

    $form = $crawler->filter('form[name="form"]')->form([
      'form[name]' => 'Created',
      'form[category]' => 'news',
    ]);
    $this->client->submit($form);
    self::assertResponseRedirects();
    $page = $this->doctrine()->getRepository(Page::class)->findOneBy(['name' => 'Created']);
    self::assertInstanceOf(Page::class, $page);
    self::assertSame("/austral-admin/pages/{$page->getId()}/edit", $this->client->getResponse()->headers->get('Location'));
  }

  public function testCreateWithErrorsIsUnprocessable(): void
  {
    $crawler = $this->client->request('GET', '/austral-admin/pages/new');
    $form = $crawler->filter('form[name="form"]')->form(['form[name]' => '']);
    $this->client->submit($form);

    self::assertResponseStatusCodeSame(422);
    self::assertSame(0, $this->doctrine()->getRepository(Page::class)->count([]));
  }

  public function testEditAndGoBackToTheList(): void
  {
    $page = $this->page('Alpha');
    $this->client->request('GET', '/austral-admin/pages?page=1');
    $crawler = $this->client->request('GET', "/austral-admin/pages/{$page->getId()}/edit");
    self::assertResponseIsSuccessful();

    $form = $crawler->filter('form[name="form"]')->selectButton('Save and go back to the list')->form(['form[name]' => 'Renamed']);
    $this->client->submit($form);

    self::assertResponseRedirects('/austral-admin/pages?page=1', 302, 'Back to the last URL of the list.');
    self::assertSame('Renamed', $this->reload($page)?->getName());
  }

  public function testAnEntityOutsideTheRestrictionsIsNotFound(): void
  {
    $secret = $this->page('Hidden', 'secret');
    $token = $this->csrfToken();

    $this->client->request('GET', "/austral-admin/pages/{$secret->getId()}/edit");
    self::assertResponseStatusCodeSame(404);
    $this->client->request('POST', "/austral-admin/pages/{$secret->getId()}/delete", ['_token' => $token]);
    self::assertResponseStatusCodeSame(404);
    $this->client->request('POST', "/austral-admin/pages/{$secret->getId()}/toggle/isPublished?value=1", ['_token' => $token]);
    self::assertResponseStatusCodeSame(404);
    self::assertNotNull($this->reload($secret));
  }

  public function testDeleteNeedsAPostWithTheCsrfToken(): void
  {
    $page = $this->page('Alpha');

    $this->client->request('GET', "/austral-admin/pages/{$page->getId()}/delete");
    self::assertResponseStatusCodeSame(405);
    $this->client->request('POST', "/austral-admin/pages/{$page->getId()}/delete");
    self::assertResponseStatusCodeSame(403);
    $this->client->request('POST', "/austral-admin/pages/{$page->getId()}/delete", ['_token' => 'invalid']);
    self::assertResponseStatusCodeSame(403);
    self::assertNotNull($this->reload($page));

    $this->client->request('POST', "/austral-admin/pages/{$page->getId()}/delete", ['_token' => $this->csrfToken()]);
    self::assertResponseRedirects('/austral-admin/pages');
    self::assertNull($this->reload($page));
  }

  public function testDuplicate(): void
  {
    $page = $this->page('Alpha');
    $this->client->request('POST', "/austral-admin/pages/{$page->getId()}/duplicate", ['_token' => $this->csrfToken()]);

    self::assertResponseRedirects();
    $copy = $this->doctrine()->getRepository(Page::class)->findOneBy(['name' => 'Alpha (copy)']);
    self::assertInstanceOf(Page::class, $copy, 'The copy is changed by the event of PageAdmin.');
    self::assertNotSame($page->getId(), $copy->getId());
    self::assertSame("/austral-admin/pages/{$copy->getId()}/edit", $this->client->getResponse()->headers->get('Location'));
  }

  public function testToggleChangesADeclaredColumnToOneOfItsValues(): void
  {
    $page = $this->page('Alpha');
    $token = $this->csrfToken();

    $this->client->request('POST', "/austral-admin/pages/{$page->getId()}/toggle/isPublished?value=1", ['_token' => $token]);
    self::assertResponseRedirects('/austral-admin/pages');
    self::assertTrue($this->reload($page)?->getIsPublished());

    $this->client->request('POST', "/austral-admin/pages/{$page->getId()}/toggle/status?value=online", ['_token' => $token], [], ['HTTP_ACCEPT' => 'application/json']);
    self::assertResponseIsSuccessful();
    self::assertSame(['status' => 'changed', 'id' => $page->getId(), 'field' => 'status', 'value' => 'online'], json_decode((string) $this->client->getResponse()->getContent(), true));
    self::assertSame(Page::STATUS_ONLINE, $this->reload($page)?->getStatus());
  }

  public function testToggleRefusesTheOtherColumnsAndValues(): void
  {
    $page = $this->page('Alpha');
    $token = $this->csrfToken();
    $id = $page->getId();

    $this->client->request('POST', "/austral-admin/pages/{$id}/toggle/status?value=deleted", ['_token' => $token]);
    self::assertResponseStatusCodeSame(400, 'Not a value of the column.');
    $this->client->request('POST', "/austral-admin/pages/{$id}/toggle/isPublished?value=2", ['_token' => $token]);
    self::assertResponseStatusCodeSame(400, 'Not a value of the switch.');
    $this->client->request('POST', "/austral-admin/pages/{$id}/toggle/category?value=secret", ['_token' => $token]);
    self::assertResponseStatusCodeSame(400, 'Not a switch or choices column.');
    $this->client->request('POST', "/austral-admin/pages/{$id}/toggle/display?value=online", ['_token' => $token]);
    self::assertResponseStatusCodeSame(400, 'A choices column without url is displayed only.');
    $this->client->request('POST', "/austral-admin/pages/{$id}/toggle/name?value=x", ['_token' => $token]);
    self::assertResponseStatusCodeSame(400, 'Not a column.');
    $this->client->request('POST', "/austral-admin/pages/{$id}/toggle/isPublished?value=1");
    self::assertResponseStatusCodeSame(403, 'No CSRF token.');

    $page = $this->reload($page);
    self::assertInstanceOf(Page::class, $page);
    self::assertSame('news', $page->getCategory());
    self::assertSame(Page::STATUS_DRAFT, $page->getStatus());
    self::assertFalse($page->getIsPublished());
  }

  public function testSortGivesThePositionsAgainInTheNewOrder(): void
  {
    $a = $this->page('A', position: 10);
    $b = $this->page('B', position: 20);
    $c = $this->page('C', position: 30);
    $secret = $this->page('S', 'secret', 40);

    $this->client->request('POST', '/austral-admin/pages/sort', ['_token' => $this->csrfToken(), 'ids' => [$c->getId(), $a->getId(), $b->getId()]], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
    self::assertResponseIsSuccessful();
    self::assertSame(10, $this->reload($c)?->getPosition());
    self::assertSame(20, $this->reload($a)?->getPosition());
    self::assertSame(30, $this->reload($b)?->getPosition());

    $this->client->request('POST', '/austral-admin/pages/sort', ['_token' => $this->csrfToken(), 'ids' => [$secret->getId(), $a->getId()]]);
    self::assertResponseStatusCodeSame(400, 'An entity outside the module cannot be sorted.');
    self::assertSame(40, $this->reload($secret)?->getPosition());
  }

  public function testTruncateDeletesTheEntitiesOfTheModuleOnly(): void
  {
    for ($i = 0; $i < 5; ++$i)
    {
      $this->page("Page {$i}");
    }
    $secret = $this->page('Hidden', 'secret');

    $this->client->request('POST', '/austral-admin/pages/delete-all', ['_token' => $this->csrfToken()]);
    self::assertResponseRedirects('/austral-admin/pages');
    self::assertSame(1, $this->doctrine()->getRepository(Page::class)->count([]));
    self::assertNotNull($this->reload($secret));
  }

  public function testExportCsvAndJson(): void
  {
    $this->page('Alpha', position: 1);
    $this->page('=HYPERLINK("http://evil")', position: 2);
    $this->page('Hidden', 'secret');

    $this->client->request('GET', '/austral-admin/pages/export.csv');
    self::assertResponseIsSuccessful();
    self::assertStringStartsWith('text/csv', (string) $this->client->getResponse()->headers->get('Content-Type'));
    self::assertStringContainsString('attachment; filename=pages-', (string) $this->client->getResponse()->headers->get('Content-Disposition'));
    $csv = $this->client->getInternalResponse()->getContent();
    $lines = array_values(array_filter(explode("\n", substr($csv, 3))));
    self::assertCount(3, $lines, 'Header and the two entities of the module, without pagination.');
    self::assertStringContainsString('Alpha', $lines[1]);
    self::assertStringContainsString("'=HYPERLINK", $lines[2], 'A formula is neutralized.');
    self::assertStringNotContainsString('Hidden', $csv);

    $this->client->request('GET', '/austral-admin/pages/export.json');
    self::assertResponseIsSuccessful();
    $json = json_decode($this->client->getInternalResponse()->getContent(), true);
    self::assertIsArray($json);
    self::assertCount(2, $json);
    self::assertSame('Alpha', $json[0]['name']);
    self::assertSame('news', $json[0]['category']);

    $this->client->request('GET', '/austral-admin/pages/export.xlsx');
    self::assertResponseIsSuccessful();
    self::assertStringStartsWith('PK', $this->client->getInternalResponse()->getContent());

    $this->client->request('GET', '/austral-admin/pages/export.pdf');
    self::assertResponseStatusCodeSame(404);
  }

  public function testExtraAction(): void
  {
    $page = $this->page('Alpha');

    $this->client->request('GET', "/austral-admin/pages/{$page->getId()}/publish");
    self::assertResponseRedirects('/austral-admin/pages', 302, 'Without response nor template, back to the list.');
    $this->client->request('POST', "/austral-admin/pages/{$page->getId()}/publish");
    self::assertResponseStatusCodeSame(403, 'A POST needs the CSRF token.');
    $this->client->request('POST', "/austral-admin/pages/{$page->getId()}/publish", ['_token' => $this->csrfToken()]);
    self::assertResponseRedirects('/published');
    self::assertTrue($this->reload($page)?->getIsPublished());
  }
}
