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

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * Austral Admin Modules Prepender.
 *
 * For the bridges of the other bundles: in the prepend() of their extension, the modules of a YAML file
 * ("austral_admin: modules: ...") are added before the configuration of the application, which can change or
 * disable them. Nothing happens when admin-bundle is not enabled.
 *
 *   public function prepend(ContainerBuilder $container): void
 *   {
 *     AdminModulesPrepender::prepend($container, __DIR__.'/../Bridge/Admin/austral_admin.yaml');
 *   }
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminModulesPrepender
{
  /**
   * @param ContainerBuilder $container
   * @param string           $file
   *
   * @return bool True when the modules are added
   */
  public static function prepend(ContainerBuilder $container, string $file): bool
  {
    if (!$container->hasExtension('austral_admin'))
    {
      return false;
    }
    $content = Yaml::parseFile($file);
    $config = \is_array($content) ? ($content['austral_admin'] ?? null) : null;
    if (!\is_array($config))
    {
      throw new \InvalidArgumentException(\sprintf('The file "%s" must contain an "austral_admin" key.', $file));
    }
    $container->addResource(new FileResource($file));
    $container->prependExtensionConfig('austral_admin', $config);

    return true;
  }
}
