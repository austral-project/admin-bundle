<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\DependencyInjection;

use Austral\ToolsBundle\AustralTools;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use \Exception;

/**
 * Austral Admin Extension.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AustralAdminExtension extends Extension implements PrependExtensionInterface
{
  /**
   * {@inheritdoc}
   * @throws Exception
   */
  public function load(array $configs, ContainerBuilder $container)
  {
    $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
    $loader->load('austral_admin.yaml');
    $loader->load('parameters.yaml');


    $configs[0]['default_class'] = $container->getParameter('austral.admin.default.class');
    $configs[0]['actions_entities'] = $container->getParameter('austral.admin.modules.actions.entities');
    $configs[0]['data_reload_js'] = $container->getParameter('austral.admin.modules.data_reload_js');
    $configs[0]['language'] = array_replace_recursive($container->getParameter('austral.admin.language'), AustralTools::getValueByKey($configs[0], 'language', array()));
    $configs[0]['compression_gzip'] = $container->getParameter('austral.admin.modules.enabled.compression_gzip');
    $configs[0]['download'] = array_replace_recursive($container->getParameter('austral.admin.download'), AustralTools::getValueByKey($configs[0], 'download', array()));
    $configs[0]['project'] = array_replace_recursive($container->getParameter('austral.admin.project'), AustralTools::getValueByKey($configs[0], 'project', array()));
    $configs[0]["modules"] = AustralTools::getValueByKey($configs[0], "modules", array());

    $configuration = new Configuration();
    $config = $this->processConfiguration($configuration, $configs);

    $container->setParameter('austral_admin', $config);

    $loader->load('services.yaml');
    $loader->load('command.yaml');


    $this->loadConfigToAustralBundle($container, $loader);
  }

  public function prepend(ContainerBuilder $container)
  {
    $container->setParameter("austral.admin.path", "austral-admin");
  }

  /**
   * @param ContainerBuilder $container
   * @param YamlFileLoader $loader
   *
   * @throws \Exception
   */
  protected function loadConfigToAustralBundle(ContainerBuilder $container, YamlFileLoader $loader)
  {
    $bundlesConfigPath = $container->getParameter("kernel.project_dir")."/config/bundles.php";
    if(file_exists($bundlesConfigPath))
    {
      $contents = require $bundlesConfigPath;
      if(array_key_exists("Austral\FormBundle\AustralFormBundle", $contents))
      {
        $loader->load('austral_form.yaml');
      }
    }
  }

}
