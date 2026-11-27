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

namespace Austral\AdminBundle\Tests\Fixtures;

use Austral\AdminBundle\AustralAdminBundle;
use Austral\AdminBundle\Tests\Fixtures\Admin\PageAdmin;
use Austral\AdminBundle\Tests\Fixtures\Admin\ReportAdmin;
use Austral\AdminBundle\Tests\Fixtures\Entity\Group;
use Austral\AdminBundle\Tests\Fixtures\Entity\Page;
use Austral\AdminBundle\Tests\Fixtures\Entity\Role;
use Austral\AdminBundle\Tests\Fixtures\Entity\User;
use Austral\AdminBundle\Tests\Fixtures\Variant\CategoryVariantProvider;
use Austral\DesignBundle\AustralDesignBundle;
use Austral\EntityBundle\AustralEntityBundle;
use Austral\EntityBundle\EntityManager\EntityManager;
use Austral\EntityFileBundle\AustralEntityFileBundle;
use Austral\FilterBundle\AustralFilterBundle;
use Austral\FormBundle\AustralFormBundle;
use Austral\HttpBundle\AustralHttpBundle;
use Austral\ListBundle\AustralListBundle;
use Austral\SecurityBundle\AustralSecurityBundle;
use Austral\SecurityBundle\Entity\Interfaces\GroupInterface;
use Austral\SecurityBundle\Entity\Interfaces\RoleInterface;
use Austral\SecurityBundle\Entity\Interfaces\UserInterface;
use Austral\ToolsBundle\AustralToolsBundle;
use Composer\InstalledVersions;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * Application of the functional tests: the admin on SQLite, the modules "pages" (with restriction, toggle,
 * sort, export, extra action), "articles" (variants by category), the container "library" and "report"
 * (attribute).
 */
final class AdminKernel extends Kernel
{
  use MicroKernelTrait;

  /**
   * @param array<string, mixed> $adminConfig Merged into the configuration of austral_admin
   * @param array<string, mixed> $httpConfig  Configuration of austral_http
   */
  public function __construct(string $environment = 'test', bool $debug = false, private readonly array $adminConfig = [], private readonly array $httpConfig = [])
  {
    parent::__construct($environment, $debug);
  }

  public function registerBundles(): iterable
  {
    yield new FrameworkBundle();
    yield new SecurityBundle();
    yield new TwigBundle();
    yield new TwigComponentBundle();
    yield new DoctrineBundle();
    yield new AustralToolsBundle();
    yield new AustralEntityBundle();
    yield new AustralHttpBundle();
    yield new AustralEntityFileBundle();
    yield new AustralFormBundle();
    yield new AustralListBundle();
    yield new AustralFilterBundle();
    yield new AustralSecurityBundle();
    yield new AustralDesignBundle();
    yield new AustralAdminBundle();
  }

  public function getProjectDir(): string
  {
    return \dirname(__DIR__, 2);
  }

  public function getCacheDir(): string
  {
    return sys_get_temp_dir().'/austral-admin-tests/'.md5(serialize([$this->adminConfig, $this->httpConfig])).'/cache';
  }

  public function getLogDir(): string
  {
    return sys_get_temp_dir().'/austral-admin-tests/logs';
  }

  public function getDatabasePath(): string
  {
    return \dirname($this->getCacheDir()).'/test.sqlite';
  }

  protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
  {
    $loader->load(function (ContainerBuilder $container): void {
      $container->loadFromExtension('framework', [
        'secret' => 'test',
        'test' => true,
        'http_method_override' => false,
        'handle_all_throwables' => true,
        'php_errors' => ['log' => true],
        'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
        'csrf_protection' => true,
        'form' => true,
        'validation' => ['email_validation_mode' => 'html5'],
        'translator' => ['default_path' => '%kernel.project_dir%/translations', 'fallbacks' => ['en']],
        'default_locale' => 'en',
        'property_access' => true,
        'router' => ['utf8' => true],
      ]);
      $container->loadFromExtension('twig', ['strict_variables' => true]);
      $container->loadFromExtension('twig_component', ['anonymous_template_directory' => 'components/']);
      $container->loadFromExtension('austral_http', $this->httpConfig);
      $container->loadFromExtension('doctrine', [
        'dbal' => ['driver' => 'pdo_sqlite', 'path' => $this->getDatabasePath()],
        'orm' => self::nativeLazyObjects() + [
          'mappings' => ['Test' => ['type' => 'attribute', 'dir' => __DIR__.'/Entity', 'prefix' => 'Austral\AdminBundle\Tests\Fixtures\Entity', 'is_bundle' => false]],
          'resolve_target_entities' => [UserInterface::class => User::class, RoleInterface::class => Role::class, GroupInterface::class => Group::class],
        ],
      ]);
      $container->loadFromExtension('austral_security', [
        'entities' => ['user' => User::class, 'role' => Role::class, 'group' => Group::class],
        'login_link' => ['firewall' => 'austral_admin'],
      ]);
      $container->loadFromExtension('security', [
        'role_hierarchy' => ['ROLE_ROOT' => ['ROLE_ADMIN_ACCESS', 'ROLE_USER_ACCESS']],
        'password_hashers' => [UserInterface::class => 'plaintext'],
        'providers' => ['austral_security_user' => ['id' => 'austral.security.user_provider']],
        'firewalls' => [
          'austral_admin' => [
            'pattern' => '^%austral_admin.path%',
            'lazy' => true,
            'provider' => 'austral_security_user',
            'user_checker' => 'austral.security.user_checker',
            'form_login' => [
              'login_path' => 'austral_admin_security_login',
              'check_path' => 'austral_admin_security_login',
              'default_target_path' => 'austral_admin_index',
              'enable_csrf' => true,
            ],
            'login_link' => [
              'check_route' => 'austral_admin_security_login_link_check',
              'signature_properties' => ['id', 'email'],
              'lifetime' => 600,
              'check_post_only' => true,
              'login_path' => 'austral_admin_security_login',
              'default_target_path' => 'austral_admin_index',
            ],
            'logout' => ['path' => 'austral_admin_security_logout', 'target' => 'austral_admin_security_login'],
          ],
        ],
        'access_control' => [
          ['path' => '^%austral_admin.path%/(login|logout|password)', 'roles' => 'PUBLIC_ACCESS'],
          ['path' => '^%austral_admin.path%', 'roles' => 'ROLE_ADMIN_ACCESS'],
        ],
      ]);
      $container->loadFromExtension('austral_admin', array_replace_recursive([
        'build' => __DIR__.'/build/entrypoints.json',
        'security' => ['password_reset' => true],
        'languages' => ['content' => []],
        'modules' => [
          'pages' => [
            'name' => 'Pages',
            'class' => PageAdmin::class,
            'entity_manager' => 'test.entity_manager.page',
            'sortable' => true,
            'truncate' => true,
            'downloadFormats' => ['csv', 'json', 'xlsx'],
            'extendActions' => ['publish' => 'publish'],
            'navigation' => ['position' => 10],
          ],
          'library' => [
            'name' => 'Library',
            'navigation' => ['position' => 25],
            'children' => [
              'books' => ['class' => PageAdmin::class, 'entity_manager' => 'test.entity_manager.page', 'navigation' => ['position' => 2]],
              'authors' => ['class' => PageAdmin::class, 'entity_manager' => 'test.entity_manager.page', 'navigation' => ['position' => 1]],
            ],
          ],
          'articles' => [
            'name' => 'Articles',
            'class' => PageAdmin::class,
            'entity_manager' => 'test.entity_manager.page',
            'disabledActions' => ['duplicate', 'change'],
            'navigation' => ['position' => 20],
          ],
        ],
      ], $this->adminConfig));

      $container->register('logger', NullLogger::class);
      $container->register('test.entity_manager.page', EntityManager::class)
        ->setArguments([new Reference('doctrine.orm.entity_manager'), new Reference('event_dispatcher'), Page::class])
        ->setPublic(true);
      $container->register(PageAdmin::class, PageAdmin::class)->addTag('austral.admin')->setPublic(true);
      $container->register(ReportAdmin::class, ReportAdmin::class)->setAutoconfigured(true);
      $container->register(MailCollector::class, MailCollector::class)->setPublic(true)
        ->addTag('kernel.event_listener', ['event' => 'austral.security.password_reset.requested', 'method' => 'onPasswordReset'])
        ->addTag('kernel.event_listener', ['event' => 'austral.security.login_link.requested', 'method' => 'onLoginLink']);
      $container->register(CategoryVariantProvider::class, CategoryVariantProvider::class)->addTag('austral.admin.module_variants');
    });
  }

  /**
   * Native lazy objects: an option of DoctrineBundle 2.x, always on (option deprecated) from 3.1.
   *
   * @return array<string, bool>
   */
  private static function nativeLazyObjects(): array
  {
    $version = InstalledVersions::getVersion('doctrine/doctrine-bundle') ?? '0';

    return version_compare($version, '3.1', '>=') ? [] : ['enable_native_lazy_objects' => true];
  }

  protected function configureRoutes(RoutingConfigurator $routes): void
  {
    $routes->import('.', 'austral_admin');
  }
}
