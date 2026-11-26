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

namespace Austral\AdminBundle\Tests\Functional;

/**
 * compression_gzip.admin of http-bundle overrides compression_gzip.default for the pages of the admin.
 */
final class CompressionAdminTest extends AdminWebTestCase
{
  protected static function httpConfig(): array
  {
    return ['compression_gzip' => ['default' => true, 'admin' => false]];
  }

  public function testTheAdminKeyOverridesTheDefaultCompression(): void
  {
    $this->login('root');

    $this->client->request('GET', '/austral-admin/', [], [], ['HTTP_ACCEPT_ENCODING' => 'gzip']);

    self::assertResponseIsSuccessful();
    self::assertResponseNotHasHeader('Content-Encoding');
  }
}
