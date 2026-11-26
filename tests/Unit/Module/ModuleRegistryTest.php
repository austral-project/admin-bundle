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

namespace Austral\AdminBundle\Tests\Unit\Module;

use Austral\AdminBundle\Exception\ModuleNotFoundException;
use Austral\AdminBundle\Module\ModuleDefinition;
use Austral\AdminBundle\Module\ModuleRegistry;
use Austral\AdminBundle\Tests\Fixtures\Entity\Page;
use Austral\AdminBundle\Tests\Unit\ModuleDefinitions;
use PHPUnit\Framework\TestCase;

final class ModuleRegistryTest extends TestCase
{
  private function registry(): ModuleRegistry
  {
    return new ModuleRegistry([
      'security' => ModuleDefinitions::raw('security', ['children' => ['security/users', 'security/groups'], 'navigation' => ['enabled' => true, 'position' => 100]]),
      'security/users' => ModuleDefinitions::raw('security/users', ['navigation' => ['enabled' => true, 'position' => 2]]),
      'security/groups' => ModuleDefinitions::raw('security/groups', ['navigation' => ['enabled' => true, 'position' => 1]]),
      'pages' => ModuleDefinitions::raw('pages', ['admin' => 'admin', 'entity_manager' => 'em', 'entity' => Page::class]),
      'news' => ModuleDefinitions::raw('news', ['admin' => 'admin', 'entity_manager' => 'em', 'entity' => Page::class]),
    ]);
  }

  public function testRootsAndChildrenAreInNavigationOrder(): void
  {
    $registry = $this->registry();
    $paths = static fn (array $definitions): array => array_map(static fn (ModuleDefinition $definition): string => $definition->getPath(), $definitions);

    self::assertSame(['pages', 'news', 'security'], $paths($registry->roots()), 'Same position: configuration order.');
    self::assertSame(['security/groups', 'security/users'], $paths($registry->children($registry->get('security'))));
    self::assertSame(['security'], $paths($registry->ancestors($registry->get('security/users'))));
    self::assertSame(['pages', 'news'], $paths($registry->findByEntity(new Page())));
    self::assertSame('security/users', $registry->findByKey('users')?->getPath());
    self::assertNull($registry->findByKey('unknown'));
  }

  public function testDefinition(): void
  {
    $definition = $this->registry()->get('security/users');

    self::assertTrue($definition->isContainer());
    self::assertSame('securityUsers', $definition->getTranslationKey());
    self::assertSame('users', $definition->getRoute());
    self::assertSame('security/users', $definition->getSecurityKey());
    self::assertSame('security', $definition->getParentPath());
  }

  public function testUnknownModule(): void
  {
    $this->expectException(ModuleNotFoundException::class);
    $this->registry()->get('unknown');
  }

  public function testUnknownKeyOfADefinition(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    ModuleDefinition::fromArray(ModuleDefinitions::raw('pages', ['data_hydrate_class' => 'X']));
  }
}
