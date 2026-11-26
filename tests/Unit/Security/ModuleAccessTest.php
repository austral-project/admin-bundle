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

namespace Austral\AdminBundle\Tests\Unit\Security;

use Austral\AdminBundle\Module\ModuleRegistry;
use Austral\AdminBundle\Security\ModuleAccess;
use Austral\AdminBundle\Tests\Unit\ModuleDefinitions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ModuleAccessTest extends TestCase
{
  public function testAvailableActionsOfAnEntityModule(): void
  {
    $definition = ModuleDefinitions::entity();
    foreach (['list', 'create', 'edit', 'duplicate', 'delete', 'toggle', 'change'] as $action)
    {
      self::assertTrue(ModuleAccess::isAvailable($definition, $action), $action);
    }
    foreach (['truncate', 'sort', 'export', 'download', 'publish', 'index'] as $action)
    {
      self::assertFalse(ModuleAccess::isAvailable($definition, $action), $action);
    }

    $definition = ModuleDefinitions::entity('pages', ['truncate' => true, 'sortable' => 'position', 'download_formats' => ['csv'], 'extend_actions' => ['publish' => 'publish'], 'disabled_actions' => ['duplicate', 'toggle']]);
    foreach (['truncate', 'sort', 'export', 'publish'] as $action)
    {
      self::assertTrue(ModuleAccess::isAvailable($definition, $action), $action);
    }
    self::assertFalse(ModuleAccess::isAvailable($definition, 'duplicate'));
    self::assertFalse(ModuleAccess::isAvailable($definition, 'change'), 'The 3.1 name of toggle.');
  }

  public function testAvailableActionsOfPageAndContainerModules(): void
  {
    $page = ModuleDefinitions::page('tools', ['actions' => ['index' => 'index', 'refresh' => 'refresh']]);
    self::assertTrue(ModuleAccess::isAvailable($page, 'refresh'));
    self::assertFalse(ModuleAccess::isAvailable($page, 'edit'));

    $container = ModuleDefinitions::container();
    self::assertTrue(ModuleAccess::isAvailable($container, 'list'));
    self::assertFalse(ModuleAccess::isAvailable($container, 'create'));
  }

  public function testAContainerIsOpenToTheUsersOfItsChildren(): void
  {
    $registry = new ModuleRegistry([
      'security' => ModuleDefinitions::raw('security', ['children' => ['security/users']]),
      'security/users' => ModuleDefinitions::raw('security/users', ['admin' => 'a', 'entity_manager' => 'em']),
      'tools' => ModuleDefinitions::raw('tools'),
    ]);
    $checker = $this->createStub(AuthorizationCheckerInterface::class);
    $checker->method('isGranted')->willReturnCallback(static fn (mixed $role): bool => 'ROLE_SECURITY_USERS' === $role);
    $access = new ModuleAccess($checker, $registry);

    self::assertTrue($access->isGranted($registry->get('security'), 'list'));
    self::assertFalse($access->isGranted($registry->get('tools'), 'list'));
  }

  public function testAnActionNeedsItsRoleAndTheRoleOfTheModule(): void
  {
    $granted = ['ROLE_PAGES_EDIT' => true];
    $checker = $this->createStub(AuthorizationCheckerInterface::class);
    $checker->method('isGranted')->willReturnCallback(static function (mixed $role) use (&$granted): bool {
      return $granted[$role] ?? false;
    });
    $access = new ModuleAccess($checker);
    $definition = ModuleDefinitions::entity();

    self::assertFalse($access->isGranted($definition, 'edit'), 'Without ROLE_PAGES.');
    $granted['ROLE_PAGES'] = true;
    self::assertTrue($access->isGranted($definition, 'edit'));
    self::assertFalse($access->isGranted($definition, 'delete'));
    self::assertFalse($access->isGranted($definition, 'truncate'), 'Not available.');
  }
}
