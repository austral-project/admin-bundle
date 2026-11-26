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

namespace Austral\AdminBundle\Tests\Unit\Template;

use Austral\AdminBundle\Template\TemplateParameters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class TemplateParametersTest extends TestCase
{
  public function testTheStatusIsOkByDefault(): void
  {
    self::assertSame(Response::HTTP_OK, new TemplateParameters()->getStatus());
  }

  public function testTheParametersAreMergedOnDemand(): void
  {
    $templateParameters = new TemplateParameters();
    $templateParameters->addParameters('language', ['interface' => 'en', 'current' => 'en']);
    $templateParameters->addParameters('language', ['current' => 'fr'], true);

    self::assertSame(['interface' => 'en', 'current' => 'fr'], $templateParameters->getParameter('language'));
  }

  public function testResetForgetsThePageOfThePreviousRequest(): void
  {
    $templateParameters = new TemplateParameters();
    $templateParameters->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $templateParameters->setPath('@AustralAdmin/module/list.html.twig');
    $templateParameters->addParameters('user', 'root');
    $templateParameters->setName('list');

    $templateParameters->reset();

    self::assertNull($templateParameters->getPath());
    self::assertSame('default', $templateParameters->getName());
    self::assertSame(Response::HTTP_OK, $templateParameters->getStatus());
    self::assertFalse($templateParameters->hasParameter('user'));
  }
}
