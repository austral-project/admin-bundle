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

use Austral\AdminBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
  /**
   * @param list<array<string, mixed>> $configs
   *
   * @return array<string, mixed>
   */
  private static function process(array $configs): array
  {
    return new Processor()->processConfiguration(new Configuration(), $configs);
  }

  public function testDefaults(): void
  {
    $config = self::process([]);

    self::assertSame('/austral-admin', $config['path']);
    self::assertSame('%kernel.project_dir%/public/build/entrypoints.json', $config['build']);
    self::assertSame(['en', 'fr'], $config['languages']['interface']);
    self::assertSame([], $config['languages']['content']);
    self::assertSame(30, $config['list']['per_page']);
    self::assertNull($config['mail']['from']);
    self::assertFalse($config['security']['password_reset']);
    self::assertSame([], $config['modules']);
  }

  public function testTheApplicationChangesTheModulesOfABundle(): void
  {
    $bundle = ['modules' => ['security' => ['name' => 'Security', 'children' => [
      'users' => ['class' => 'UserAdmin', 'entity_manager' => 'em', 'downloadFormats' => ['csv', 'json'], 'disabledActions' => ['duplicate']],
      'groups' => ['class' => 'GroupAdmin', 'entity_manager' => 'em'],
    ]]]];
    $application = ['modules' => ['security' => ['children' => [
      'users' => ['downloadFormats' => ['csv']],
      'groups' => ['enabled' => false],
    ]]]];

    $users = self::process([$bundle, $application])['modules']['security']['children']['users'];
    $groups = self::process([$bundle, $application])['modules']['security']['children']['groups'];

    self::assertSame('UserAdmin', $users['class'], 'Deep merge: the keys of the bundle stay.');
    self::assertSame(['csv'], $users['downloadFormats'], 'A list is replaced, not merged.');
    self::assertSame(['duplicate'], $users['disabledActions']);
    self::assertFalse($groups['enabled']);
  }

  public function testAProjectGivesItsOwnBuild(): void
  {
    self::assertSame('/var/www/project/public/admin/entrypoints.json', self::process([['build' => '/var/www/project/public/admin/entrypoints.json']])['build']);
  }

  public function testAnEmptyBuildIsRefused(): void
  {
    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessage('The build must be the path of an entrypoints.json file');
    self::process([['build' => ' ']]);
  }

  public function testTheKeysOfThe31AreRefused(): void
  {
    $this->expectException(InvalidConfigurationException::class);
    self::process([['modules' => ['pages' => ['data_hydrate_class' => 'X']]]]);
  }
}
