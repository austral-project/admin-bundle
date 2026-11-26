<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle;

use Austral\AdminBundle\DependencyInjection\Compiler\ModuleRegistryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Austral Admin Bundle.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class AustralAdminBundle extends Bundle
{
  /**
   * @param ContainerBuilder $container
   *
   * @return void
   */
  public function build(ContainerBuilder $container): void
  {
    parent::build($container);
    $container->addCompilerPass(new ModuleRegistryPass());
  }
}
