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

namespace Austral\AdminBundle\Tests\Unit\DependencyInjection;

use Austral\AdminBundle\DependencyInjection\AustralAdminExtension;
use Austral\AdminBundle\DependencyInjection\Compiler\ModuleRegistryPass;
use Austral\AdminBundle\DependencyInjection\Configuration;
use Austral\AdminBundle\Module\ModuleFactory;
use Austral\AdminBundle\Module\ModuleRegistry;
use Austral\AdminBundle\Tests\Fixtures\Admin\PageAdmin;
use Austral\AdminBundle\Tests\Fixtures\Entity\Page;
use Austral\AdminBundle\Tests\Fixtures\Variant\CategoryVariantProvider;
use Austral\EntityBundle\EntityManager\EntityManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class ModuleRegistryPassTest extends TestCase
{
  /**
   * @param array<string, mixed> ...$modules The "modules" of each configuration, the application last
   */
  private static function container(array ...$modules): ContainerBuilder
  {
    $container = new ContainerBuilder();
    // As the extension: each configuration checked, then given as it is
    new Processor()->processConfiguration(new Configuration(), array_map(static fn (array $config): array => ['modules' => $config], $modules));
    $container->setParameter(AustralAdminExtension::PARAMETER_MODULES, array_values($modules));
    $container->setParameter('app.page.class', Page::class);
    $container->register(ModuleRegistry::class, ModuleRegistry::class);
    $container->register(ModuleFactory::class, ModuleFactory::class);
    $container->register('app.entity_manager.page', EntityManager::class)->setArgument('$class', '%app.page.class%');
    $container->register('app.entity_manager.unknown', EntityManager::class);
    $container->register(PageAdmin::class, PageAdmin::class)->addTag(AustralAdminExtension::TAG_ADMIN);
    $container->register(CategoryVariantProvider::class, CategoryVariantProvider::class)->addTag(AustralAdminExtension::TAG_VARIANT_PROVIDER);

    return $container;
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  private static function definitions(ContainerBuilder $container): array
  {
    new ModuleRegistryPass()->process($container);

    /* @var array<string, array<string, mixed>> */
    return $container->getDefinition(ModuleRegistry::class)->getArgument('$rawDefinitions');
  }

  public function testTheDefinitionsAreNormalized(): void
  {
    $definitions = self::definitions(self::container([
      'articles' => ['class' => PageAdmin::class, 'entity_manager' => 'app.entity_manager.page', 'sortable' => true, 'disabledActions' => ['change', 'download'], 'downloadFormats' => ['CSV', 'csv']],
      'website' => ['children' => [
        'pages' => ['route' => 'web-pages', 'class' => PageAdmin::class, 'entity_manager' => 'app.entity_manager.unknown', 'entity' => Page::class, 'sortable' => 'rank'],
        'off' => ['enabled' => false, 'children' => ['child' => []]],
      ]],
    ]));

    self::assertSame(['articles', 'website', 'website/web-pages'], array_keys($definitions));
    self::assertSame(Page::class, $definitions['articles']['entity'], 'The class of the entity manager, its parameter resolved.');
    self::assertSame('position', $definitions['articles']['sortable']);
    self::assertSame(['toggle', 'export'], $definitions['articles']['disabled_actions'], 'The names of the 3.1 are replaced.');
    self::assertSame(['csv'], $definitions['articles']['download_formats']);
    self::assertSame([CategoryVariantProvider::class], $definitions['articles']['variant_providers'], 'CategoryVariantProvider supports the key "articles".');
    self::assertSame(['website/web-pages'], $definitions['website']['children']);
    self::assertSame('rank', $definitions['website/web-pages']['sortable']);
    self::assertSame([], $definitions['website/web-pages']['variant_providers']);
  }

  public function testAnAdminClassThatIsNotAServiceIsRegistered(): void
  {
    $container = self::container(['tools' => ['class' => ToolAdmin::class, 'actions' => ['index' => 'index']]]);
    self::definitions($container);

    self::assertTrue($container->getDefinition(ToolAdmin::class)->hasTag(AustralAdminExtension::TAG_ADMIN));
  }

  public function testTheRoutesHashChangesWithTheModules(): void
  {
    $first = self::container(['pages' => ['class' => PageAdmin::class, 'entity_manager' => 'app.entity_manager.page']]);
    $second = self::container(['pages' => ['class' => PageAdmin::class, 'entity_manager' => 'app.entity_manager.page', 'truncate' => true]]);
    self::definitions($first);
    self::definitions($second);

    self::assertNotSame($first->getParameter(ModuleRegistryPass::PARAMETER_ROUTES_HASH), $second->getParameter(ModuleRegistryPass::PARAMETER_ROUTES_HASH));
  }

  /**
   * @return iterable<string, array{array<string, mixed>, string}>
   */
  public static function errors(): iterable
  {
    yield 'unknown entity manager' => [['pages' => ['class' => PageAdmin::class, 'entity_manager' => 'missing']], 'is not a service'];
    yield 'entity module without class' => [['pages' => ['entity_manager' => 'app.entity_manager.page']], 'needs an Admin class'];
    yield 'unknown class' => [['pages' => ['class' => 'App\\Missing', 'entity_manager' => 'app.entity_manager.page']], 'neither a service nor a class'];
    yield 'not an Admin' => [['pages' => ['class' => \ArrayObject::class, 'entity_manager' => 'app.entity_manager.page']], 'must extend'];
    yield 'invalid route' => [['pages' => ['route' => 'a/b']], 'must contain only'];
    yield 'same path' => [['pages' => [], 'other' => ['route' => 'pages']], 'Two admin modules'];
    yield 'page without index' => [['tools' => ['class' => ToolAdmin::class, 'actions' => ['refresh' => 'refresh']]], 'needs an action "index"'];
    yield 'reserved action' => [['pages' => ['class' => PageAdmin::class, 'entity_manager' => 'app.entity_manager.page', 'extendActions' => ['edit' => 'edit']]], 'not a valid name'];
    yield 'unknown format' => [['pages' => ['class' => PageAdmin::class, 'entity_manager' => 'app.entity_manager.page', 'downloadFormats' => ['pdf']]], 'is unknown'];
    yield 'list disabled' => [['pages' => ['class' => PageAdmin::class, 'entity_manager' => 'app.entity_manager.page', 'disabledActions' => ['list']]], 'cannot be disabled'];
  }

  /**
   * @param array<string, mixed> $modules
   */
  #[DataProvider('errors')]
  public function testAMistakeFailsAtTheCompilation(array $modules, string $message): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    self::definitions(self::container($modules));
  }

  public function testTheModulesOfTheAttributes(): void
  {
    $container = self::container(['catalog' => ['name' => 'Catalog']]);
    $container->register(CategoryAdmin::class, CategoryAdmin::class)->addTag(AustralAdminExtension::TAG_ADMIN);
    $definitions = self::definitions($container);

    self::assertSame(['catalog', 'catalog/category', 'tags'], array_keys($definitions));
    self::assertSame(CategoryAdmin::class, $definitions['catalog/category']['admin']);
    self::assertSame('category', $definitions['catalog/category']['key'], 'The key from the class name.');
    self::assertSame('position', $definitions['catalog/category']['sortable']);
    self::assertSame(Page::class, $definitions['catalog/category']['entity']);
    self::assertSame(['catalog/category'], $definitions['catalog']['children']);
    self::assertSame(['csv'], $definitions['tags']['download_formats'], 'The attribute is repeatable.');
    self::assertSame(['publish' => 'publish'], $definitions['tags']['extend_actions']);
  }

  public function testTheYamlWinsOverTheAttributes(): void
  {
    $bundle = ['catalog' => ['name' => 'Catalog', 'children' => ['category' => ['name' => 'From a bundle']]]];
    $application = ['catalog' => ['children' => ['category' => ['downloadFormats' => ['json']]]], 'tags' => ['enabled' => false]];
    $container = self::container($bundle, $application);
    $container->register(CategoryAdmin::class, CategoryAdmin::class)->addTag(AustralAdminExtension::TAG_ADMIN);
    $definitions = self::definitions($container);

    self::assertSame('From a bundle', $definitions['catalog/category']['name']);
    self::assertSame(['json'], $definitions['catalog/category']['download_formats']);
    self::assertSame(CategoryAdmin::class, $definitions['catalog/category']['admin'], 'The values not given in YAML stay.');
    self::assertArrayNotHasKey('tags', $definitions);
  }

  public function testTheParentOfAnAttributeMustBeAModule(): void
  {
    $container = self::container([]);
    $container->register(CategoryAdmin::class, CategoryAdmin::class)->addTag(AustralAdminExtension::TAG_ADMIN);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The parent "catalog" of the module "catalog/category"');
    self::definitions($container);
  }

  public function testAModuleIsDeclaredByOneAttribute(): void
  {
    $container = self::container(['catalog' => []]);
    $container->register(CategoryAdmin::class, CategoryAdmin::class)->addTag(AustralAdminExtension::TAG_ADMIN);
    $container->register(OtherTagsAdmin::class, OtherTagsAdmin::class)->addTag(AustralAdminExtension::TAG_ADMIN);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Two attributes AsAdminModule declare the module "tags"');
    self::definitions($container);
  }

  public function testTheFactoryReceivesItsLocators(): void
  {
    $container = self::container(['pages' => ['class' => PageAdmin::class, 'entity_manager' => 'app.entity_manager.page']]);
    self::definitions($container);
    $factory = $container->getDefinition(ModuleFactory::class);

    foreach (['$admins', '$entityManagers', '$variantProviders'] as $argument)
    {
      self::assertNotNull($factory->getArgument($argument));
    }
    self::assertInstanceOf(Definition::class, $container->getDefinition(PageAdmin::class));
  }
}
