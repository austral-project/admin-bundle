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

namespace Austral\AdminBundle\Tests;

use Austral\AdminBundle\AustralAdminBundle;
use Austral\AdminBundle\DependencyInjection\Compiler\ModuleRegistryPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

final class BundleTest extends TestCase
{
  public function testBundleCanBeInstantiated(): void
  {
    self::assertInstanceOf(BundleInterface::class, new AustralAdminBundle());
  }

  public function testTheBundleRegistersTheModulePass(): void
  {
    $container = new ContainerBuilder();
    new AustralAdminBundle()->build($container);

    $passes = array_filter($container->getCompilerPassConfig()->getPasses(), static fn (object $pass): bool => $pass instanceof ModuleRegistryPass);
    self::assertCount(1, $passes);
  }
}
