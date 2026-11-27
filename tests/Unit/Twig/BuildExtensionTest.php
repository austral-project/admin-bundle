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

namespace Austral\AdminBundle\Tests\Unit\Twig;

use Austral\AdminBundle\Twig\BuildExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * austral_admin_styles() and austral_admin_scripts() render the files of an entry of the entrypoints.json of the
 * build of the project.
 */
final class BuildExtensionTest extends TestCase
{
  private const string ENTRYPOINTS = __DIR__.'/../../Fixtures/build/entrypoints.json';

  public function testTheFunctionsAreDeclared(): void
  {
    $names = array_map(static fn ($function) => $function->getName(), new BuildExtension(self::ENTRYPOINTS)->getFunctions());

    self::assertSame(['austral_admin_styles', 'austral_admin_scripts'], $names);
  }

  public function testTheEntryOfTheAdministrationIsUsedByDefault(): void
  {
    self::assertSame(
      '<link rel="stylesheet" href="/build/shared.css">'."\n".
      '<link rel="stylesheet" href="/build/austral-admin.css">',
      new BuildExtension(self::ENTRYPOINTS)->styles()
    );
  }

  public function testTheScriptsOfAnEntryAreDeferred(): void
  {
    self::assertSame(
      '<script src="/build/runtime.js" defer></script>'."\n".
      '<script src="/build/austral-admin.js" defer></script>',
      new BuildExtension(self::ENTRYPOINTS)->scripts()
    );
  }

  public function testAnotherEntryOfTheProject(): void
  {
    self::assertSame(
      '<link rel="stylesheet" href="/build/shared.css">'."\n".
      '<link rel="stylesheet" href="/build/app.css">',
      new BuildExtension(self::ENTRYPOINTS)->styles('app')
    );
  }

  public function testAFileSharedByTwoEntriesIsRenderedOnce(): void
  {
    $extension = new BuildExtension(self::ENTRYPOINTS);
    $extension->styles();
    $extension->scripts();

    self::assertSame('<link rel="stylesheet" href="/build/app.css">', $extension->styles('app'));
    self::assertSame('<script src="/build/app.js" defer></script>', $extension->scripts('app'));
    self::assertSame('', $extension->styles());
  }

  public function testResetRendersTheFilesAgain(): void
  {
    $extension = new BuildExtension(self::ENTRYPOINTS);
    $first = $extension->styles();
    $extension->reset();

    self::assertSame($first, $extension->styles());
  }

  public function testTheUrlsArePrefixedWithTheBasePath(): void
  {
    $request = Request::create('/sub/page', 'GET', [], [], [], [
      'SCRIPT_FILENAME' => '/var/www/sub/index.php',
      'SCRIPT_NAME' => '/sub/index.php',
      'PHP_SELF' => '/sub/index.php',
    ]);
    $requestStack = new RequestStack();
    $requestStack->push($request);

    self::assertStringStartsWith(
      '<script src="/sub/build/runtime.js" defer></script>',
      new BuildExtension(self::ENTRYPOINTS, $requestStack)->scripts()
    );
  }

  public function testTheAttributesAreEscapedAndFalseOrNullAreSkipped(): void
  {
    $html = new BuildExtension(self::ENTRYPOINTS)->scripts(BuildExtension::ENTRY, ['type' => 'module', 'nonce' => '"><x', 'defer' => false, 'async' => null]);

    self::assertStringContainsString('<script src="/build/runtime.js" type="module" nonce="&quot;&gt;&lt;x"></script>', $html);
    self::assertStringNotContainsString('defer', $html);
    self::assertStringNotContainsString('async', $html);
  }

  public function testAnUnknownEntryIsRefused(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The entry "unknown" is not in the build of the administration');

    new BuildExtension(self::ENTRYPOINTS)->styles('unknown');
  }

  public function testAMissingBuildIsReported(): void
  {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The build of the administration is missing');

    new BuildExtension(__DIR__.'/missing.json')->styles();
  }

  public function testAFileThatIsNotAnEntrypointsFileIsRefused(): void
  {
    $file = tempnam(sys_get_temp_dir(), 'austral-admin-build');
    file_put_contents($file, '{"files": []}');

    try
    {
      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessage('is not an entrypoints file of Webpack Encore');

      new BuildExtension($file)->styles();
    }
    finally
    {
      unlink($file);
    }
  }
}
