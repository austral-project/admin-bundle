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

use Austral\AdminBundle\Security\ModuleRoles;
use Austral\AdminBundle\Tests\Unit\ModuleDefinitions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The roles keep the names of the 3.1: the roles saved in the groups stay valid.
 */
final class ModuleRolesTest extends TestCase
{
  /**
   * @return iterable<array{string, string, string}>
   */
  public static function roles(): iterable
  {
    yield ['security/admin-users', 'list', 'ROLE_SECURITY_ADMIN_USERS'];
    yield ['security/admin-users', 'edit', 'ROLE_SECURITY_ADMIN_USERS_EDIT'];
    yield ['security/users', 'create', 'ROLE_SECURITY_USERS_CREATE'];
    yield ['pages', 'delete', 'ROLE_PAGES_DELETE'];
    yield ['pages', 'duplicate', 'ROLE_PAGES_DUPLICATE'];
    yield ['pages', 'truncate', 'ROLE_PAGES_TRUNCATE'];
    yield ['pages', 'toggle', 'ROLE_PAGES_CHANGE'];
    yield ['pages', 'change', 'ROLE_PAGES_CHANGE'];
    yield ['pages', 'export', 'ROLE_PAGES_DOWNLOAD'];
    yield ['pages', 'sort', 'ROLE_PAGES_EDIT'];
    yield ['pages', 'publish', 'ROLE_PAGES_PUBLISH'];
    yield ['content-blocks', 'list', 'ROLE_CONTENT_BLOCKS'];
  }

  #[DataProvider('roles')]
  public function testRole(string $path, string $action, string $role): void
  {
    self::assertSame($role, ModuleRoles::role(ModuleDefinitions::entity($path), $action));
  }

  public function testTheSecurityKeyReplacesThePath(): void
  {
    self::assertSame('ROLE_PAGES_EDIT', ModuleRoles::role(ModuleDefinitions::entity('website/pages', ['security_key' => 'pages']), 'edit'));
  }

  public function testTheIndexOfAPageModuleIsTheRoleOfTheModule(): void
  {
    $definition = ModuleDefinitions::page('status-configuration', ['actions' => ['index' => 'index', 'refresh' => 'refresh']]);
    self::assertSame('ROLE_STATUS_CONFIGURATION', ModuleRoles::role($definition, 'index'));
    self::assertSame(['ROLE_STATUS_CONFIGURATION' => 'list', 'ROLE_STATUS_CONFIGURATION_REFRESH' => 'refresh'], ModuleRoles::roles($definition));
  }

  public function testTheRolesOfAnEntityModuleAreTheAvailableActions(): void
  {
    $roles = ModuleRoles::roles(ModuleDefinitions::entity('pages', ['disabled_actions' => ['delete'], 'download_formats' => ['csv'], 'extend_actions' => ['publish' => 'publish']]));

    self::assertSame([
      'ROLE_PAGES' => 'list',
      'ROLE_PAGES_CREATE' => 'create',
      'ROLE_PAGES_EDIT' => 'edit',
      'ROLE_PAGES_DUPLICATE' => 'duplicate',
      'ROLE_PAGES_CHANGE' => 'toggle',
      'ROLE_PAGES_DOWNLOAD' => 'export',
      'ROLE_PAGES_PUBLISH' => 'publish',
    ], $roles);
  }
}
