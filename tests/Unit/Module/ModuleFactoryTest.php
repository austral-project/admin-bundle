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

use Austral\AdminBundle\Exception\VariantNotFoundException;
use Austral\AdminBundle\Module\ModuleFactory;
use Austral\AdminBundle\Module\ModuleRegistry;
use Austral\AdminBundle\Security\ModuleAccess;
use Austral\AdminBundle\Tests\Fixtures\Admin\PageAdmin;
use Austral\AdminBundle\Tests\Fixtures\Variant\CategoryVariantProvider;
use Austral\AdminBundle\Tests\Unit\ModuleDefinitions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ModuleFactoryTest extends TestCase
{
  private function factory(): ModuleFactory
  {
    $registry = new ModuleRegistry([
      'articles' => ModuleDefinitions::raw('articles', ['admin' => PageAdmin::class, 'variant_providers' => [CategoryVariantProvider::class, CategoryVariantProvider::class]]),
    ]);

    return new ModuleFactory(
      $registry,
      new ModuleAccess($this->createStub(AuthorizationCheckerInterface::class)),
      new ServiceLocator([PageAdmin::class => static fn (): PageAdmin => new PageAdmin()]),
      new ServiceLocator([]),
      new ServiceLocator([CategoryVariantProvider::class => static fn (): CategoryVariantProvider => new CategoryVariantProvider()]),
    );
  }

  public function testTheVariantsOfTheUrlAreChecked(): void
  {
    $module = $this->factory()->create('articles', ['news', 'events']);

    self::assertSame(['news', 'events'], $module->getVariantKeys());
    self::assertTrue($module->hasAllVariants());
    self::assertInstanceOf(PageAdmin::class, $module->getAdmin());
    self::assertSame(['news', 'events'], array_map(static fn ($variant): string => $variant->key, $this->factory()->variants($this->factory()->create('articles'))));
    self::assertSame([], $this->factory()->variants($module), 'No level after the last one.');
  }

  public function testAnUnknownVariantFails(): void
  {
    $this->expectException(VariantNotFoundException::class);
    $this->factory()->create('articles', ['news', 'unknown']);
  }

  public function testTooManyVariantsFail(): void
  {
    $this->expectException(VariantNotFoundException::class);
    $this->factory()->create('articles', ['news', 'news', 'news']);
  }
}
