<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\DependencyInjection\Compiler;

use Austral\ToolsBundle\AustralTools;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Austral Admin Compiler.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AdminCompiler implements CompilerPassInterface
{
  /**
   * Init Configuration Austral Admin with all parameters defined
   * @var ContainerBuilder $container
   */
  public function process(ContainerBuilder $container)
  {
    $australAdminConfiguration = $container->getParameter('austral_admin');
    $allParameters = $container->getParameterBag()->all();
    $australAdminsModules = array_intersect_key($allParameters, array_flip( preg_grep( '/austral_admin\.modules\.\w/i', array_keys( $allParameters ) ) ) );
    $bundlesModules = array();
    foreach($australAdminsModules as $keyParameters => $australAdminModules)
    {
      if(is_array($australAdminModules))
      {
        foreach($australAdminModules as $moduleKey => $australAdminModule)
        {
          $bundlesModules[$moduleKey] = array_merge_recursive(AustralTools::getValueByKey($bundlesModules, $moduleKey, array()), $australAdminModule);
        }
        $container->getParameterBag()->remove($keyParameters);
      }
    }
    $australAdminConfiguration["modules"] = array_merge_recursive(AustralTools::getValueByKey($australAdminConfiguration, "modules", array()), $bundlesModules);
    $container->setParameter('austral_admin', $australAdminConfiguration);
  }
}