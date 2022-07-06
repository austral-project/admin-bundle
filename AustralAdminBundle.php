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

use Austral\AdminBundle\DependencyInjection\Compiler\AdminCompiler;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Austral Admin Bundle.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class AustralAdminBundle extends Bundle
{

  /**
   * @param ContainerBuilder $container
   */
  public function build(ContainerBuilder $container)
  {
    parent::build($container);
    $container->addCompilerPass(new AdminCompiler(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
  }
  
}
