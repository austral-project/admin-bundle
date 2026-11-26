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

namespace Austral\AdminBundle\Tests\Unit\Export;

use Austral\AdminBundle\Export\Exporter;
use PHPUnit\Framework\TestCase;

final class ExporterTest extends TestCase
{
  public function testText(): void
  {
    self::assertSame('', Exporter::text(null));
    self::assertSame('1', Exporter::text(true));
    self::assertSame('0', Exporter::text(false));
    self::assertSame('12', Exporter::text(12));
    self::assertSame('a, b', Exporter::text(['a', 'b']));
    self::assertSame('2026-11-26T10:00:00+00:00', Exporter::text(new \DateTimeImmutable('2026-11-26 10:00:00', new \DateTimeZone('UTC'))));
    self::assertSame('', Exporter::text(new \stdClass()));
  }

  public function testAFormulaIsNeutralized(): void
  {
    foreach (['=1+1', '+1', '-1', '@SUM(A1)', "\tx"] as $value)
    {
      self::assertSame("'{$value}", Exporter::neutralize($value));
    }
    self::assertSame('Alpha', Exporter::neutralize('Alpha'));
    self::assertSame('', Exporter::neutralize(''));
  }
}
