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

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Controller\SecurityController;
use Austral\AdminBundle\DependencyInjection\Compiler\ModuleRegistryPass;
use Austral\AdminBundle\EventSubscriber\HttpAdminEventSubscriber;
use Austral\AdminBundle\Export\Exporter;
use Austral\AdminBundle\Listener\ConfigurationCheckListener;
use Austral\AdminBundle\Listener\SecurityMailListener;
use Austral\AdminBundle\Page\AccountPage;
use Austral\AdminBundle\Page\ListConfigurator;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Twig\AdminExtension;
use Austral\AdminBundle\Variant\ModuleVariantProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Austral Admin Extension.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class AustralAdminExtension extends Extension implements PrependExtensionInterface
{
  public const string TAG_ADMIN = 'austral.admin';
  public const string TAG_VARIANT_PROVIDER = 'austral.admin.module_variants';

  /**
   * The "modules" of each configuration (bundles, application), merged by ModuleRegistryPass with the attributes
   * AsAdminModule, then removed.
   */
  public const string PARAMETER_MODULES = '.austral_admin.modules';

  /**
   * prepend
   * The modules of the admin (security, configuration), before the configuration of the application.
   *
   * @param ContainerBuilder $container
   *
   * @return void
   */
  public function prepend(ContainerBuilder $container): void
  {
    AdminModulesPrepender::prepend($container, __DIR__.'/../Resources/config/austral_admin.yaml');

    // Set before the other extensions are loaded: security.yaml uses it (pattern: ^%austral_admin.path%).
    $path = '/austral-admin';
    foreach ($container->getExtensionConfig('austral_admin') as $config)
    {
      if (\is_string($config['path'] ?? null) && '' !== $config['path'])
      {
        $path = $config['path'];
      }
    }
    $container->setParameter('austral_admin.path', '/'.trim($container->getParameterBag()->resolveValue($path), '/'));
  }

  /**
   * load
   *
   * @param array<array-key, mixed> $configs
   * @param ContainerBuilder        $container
   *
   * @return void
   */
  public function load(array $configs, ContainerBuilder $container): void
  {
    $config = $this->processConfiguration(new Configuration(), $configs);
    $path = '/'.trim($config['path'], '/');
    $interfaceLanguages = array_values(array_unique($config['languages']['interface']));
    $contentLanguages = array_values(array_unique($config['languages']['content']));

    $container->setParameter('austral_admin.path', $path);
    $container->setParameter('austral_admin.languages.interface', $interfaceLanguages);
    $container->setParameter('austral_admin.languages.content', $contentLanguages);
    // The configurations are merged again with the attributes AsAdminModule by ModuleRegistryPass (the YAML wins).
    $container->setParameter(self::PARAMETER_MODULES, array_values(array_filter(array_map(static fn (mixed $config): mixed => \is_array($config) ? ($config['modules'] ?? null) : null, $configs), 'is_array')));
    $container->setParameter(ModuleRegistryPass::PARAMETER_ROUTES_HASH, '');

    $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
    $loader->load('services.yaml');

    $container->getDefinition(AdminRouteLoader::class)
      ->setArgument('$path', $path)
      ->setArgument('$languages', $contentLanguages);
    $container->getDefinition(ListConfigurator::class)->setArgument('$perPage', $config['list']['per_page']);
    $container->getDefinition(Exporter::class)->setArgument('$csvDelimiter', $config['export']['csv_delimiter']);
    $container->getDefinition(SecurityController::class)->setArgument('$passwordResetEnabled', $config['security']['password_reset']);
    $container->getDefinition(AccountPage::class)->setArgument('$languages', $interfaceLanguages);
    $container->getDefinition(HttpAdminEventSubscriber::class)
      ->setArgument('$interfaceLanguages', $interfaceLanguages)
      ->setArgument('$contentLanguages', $contentLanguages);
    $container->getDefinition(AdminExtension::class)->setArgument('$config', [
      'name' => $config['name'],
      'path' => $path,
      'languages' => ['interface' => $interfaceLanguages, 'content' => $contentLanguages],
      'password_reset' => $config['security']['password_reset'],
    ]);

    $mailEnabled = null !== $config['mail']['from'] && interface_exists(MailerInterface::class);
    if ($mailEnabled)
    {
      $container->getDefinition(SecurityMailListener::class)
        ->setArgument('$from', $config['mail']['from'])
        ->setArgument('$fromName', $config['mail']['from_name']);
    }
    else
    {
      $container->removeDefinition(SecurityMailListener::class);
    }
    $container->getDefinition(ConfigurationCheckListener::class)->setArgument('$mailEnabled', $mailEnabled);

    $container->registerForAutoconfiguration(Admin::class)->addTag(self::TAG_ADMIN);
    $container->registerForAutoconfiguration(ModuleVariantProviderInterface::class)->addTag(self::TAG_VARIANT_PROVIDER);
  }

  /**
   * getNamespace
   *
   * @return string
   */
  public function getNamespace(): string
  {
    return 'https://austral.dev/schema/dic/austral_admin';
  }
}
