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

use Austral\AdminBundle\Tests\Fixtures\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Dashboard, container module, configuration page, command austral:admin:roles.
 */
final class PagesTest extends AdminWebTestCase
{
  public function testTheDashboardShowsTheModulesOfTheUser(): void
  {
    $this->login('root');
    $crawler = $this->client->request('GET', '/austral-admin/');

    self::assertResponseIsSuccessful();
    $tiles = $crawler->filter('.austral-admin-dashboard-block--modules a')->each(static fn ($link): string => (string) $link->attr('href'));
    // The modules of the test application, in navigation order (the bridges of other bundles can add theirs).
    $tiles = array_values(array_intersect($tiles, ['/austral-admin/pages', '/austral-admin/articles', '/austral-admin/library', '/austral-admin/report', '/austral-admin/status-configuration']));
    self::assertSame(['/austral-admin/pages', '/austral-admin/articles', '/austral-admin/library', '/austral-admin/report', '/austral-admin/status-configuration'], $tiles);
  }

  public function testAContainerModuleShowsItsChildren(): void
  {
    $this->login('root');
    $crawler = $this->client->request('GET', '/austral-admin/library');

    self::assertResponseIsSuccessful();
    $tiles = $crawler->filter('.austral-admin-tiles a')->each(static fn ($link): string => (string) $link->attr('href'));
    self::assertSame(['/austral-admin/library/authors', '/austral-admin/library/books'], $tiles);
  }

  public function testAModuleDeclaredByAnAttribute(): void
  {
    $this->login('root');
    $crawler = $this->client->request('GET', '/austral-admin/report');

    self::assertResponseIsSuccessful();
    self::assertStringContainsString('Report of the day', $crawler->filter('main')->text());
  }

  public function testTheConfigurationPage(): void
  {
    $this->login('root');
    $crawler = $this->client->request('GET', '/austral-admin/status-configuration');

    self::assertResponseIsSuccessful();
    self::assertCount(1, $crawler->filter('.austral-admin-check--error'), 'No HTTPS in the tests.');
    self::assertStringContainsString('HTTPS', $crawler->filter('main')->text());
  }

  public function testTheRolesCommandCreatesTheMissingRoles(): void
  {
    $application = new Application(self::$kernel ?? throw new \LogicException('No kernel.'));
    $tester = new CommandTester($application->find('austral:admin:roles'));

    $tester->execute(['--dry-run' => true]);
    self::assertStringContainsString('ROLE_LIBRARY_BOOKS_EDIT', $tester->getDisplay());
    self::assertNull($this->doctrine()->getRepository(Role::class)->findOneBy(['role' => 'ROLE_LIBRARY_BOOKS_EDIT']));

    $tester->execute([]);
    $tester->assertCommandIsSuccessful();
    $this->doctrine()->clear();
    $role = $this->doctrine()->getRepository(Role::class)->findOneBy(['role' => 'ROLE_LIBRARY_BOOKS_EDIT']);
    self::assertSame('books - edit', $role?->getName());
    self::assertNotNull($this->doctrine()->getRepository(Role::class)->findOneBy(['role' => 'ROLE_LIBRARY']), 'A container has its role.');
    self::assertNotNull($this->doctrine()->getRepository(Role::class)->findOneBy(['role' => 'ROLE_STATUS_CONFIGURATION']));
    self::assertNotNull($this->doctrine()->getRepository(Role::class)->findOneBy(['role' => 'ROLE_PAGES_PUBLISH']), 'Extra actions have a role.');
    self::assertNull($this->doctrine()->getRepository(Role::class)->findOneBy(['role' => 'ROLE_ARTICLES_DUPLICATE']), 'Disabled actions have none.');

    $tester->execute([]);
    self::assertStringContainsString('All the roles of the modules exist.', $tester->getDisplay());
  }
}
