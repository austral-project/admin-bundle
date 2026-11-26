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

use Austral\AdminBundle\Event\AdminHttpEvent;
use Austral\AdminBundle\Handler\AdminHandler;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Template\TemplateParameters;
use Austral\AdminBundle\Tests\Fixtures\Entity\User;

/**
 * The pages of the admin go through http-bundle: AdminHttpEvent, HttpAdminEventSubscriber, AdminHandler and the
 * template parameters given to the controller.
 */
final class HttpAdminEventTest extends AdminWebTestCase
{
  public function testThePageHasTheAdminHttpEventAndTheSharedTemplateParameters(): void
  {
    $this->login('root');

    $this->client->request('GET', '/austral-admin/');

    self::assertResponseIsSuccessful();
    self::assertSame(AdminHttpEvent::class, $this->client->getRequest()->attributes->get(AdminRouteLoader::ATTRIBUTE_HTTP_EVENT));
    $templateParameters = $this->templateParameters();
    self::assertSame('@AustralAdmin/dashboard.html.twig', $templateParameters->getPath());
    $user = $templateParameters->getParameter('user');
    self::assertInstanceOf(User::class, $user);
    self::assertSame('root', $user->getUsername());
    self::assertSame(['interface' => 'en', 'current' => 'en', 'default' => 'en', 'list' => []], $templateParameters->getParameter('language'));
    self::assertSame('dashboard', $templateParameters->getParameter('page'));
    self::assertFalse($this->handler()->hasModule(), 'The dashboard has no module.');
  }

  public function testTheModuleOfTheRouteIsResolvedBeforeTheController(): void
  {
    $this->login('root');

    $this->client->request('GET', '/austral-admin/pages');

    self::assertResponseIsSuccessful();
    self::assertSame('pages', $this->handler()->getModule()->getPath());
    self::assertSame($this->handler()->getModule(), $this->templateParameters()->getParameter('module'));
    self::assertSame('@AustralAdmin/module/list.html.twig', $this->templateParameters()->getPath());
  }

  public function testTheLoginPageHasTheHandlerWithoutModule(): void
  {
    $this->client->request('GET', '/austral-admin/login');

    self::assertResponseIsSuccessful();
    self::assertFalse($this->handler()->hasModule());
    self::assertNull($this->templateParameters()->getParameter('user'));
    self::assertSame('login', $this->templateParameters()->getParameter('page'));
  }

  public function testAnInvalidFormKeepsItsStatusCode(): void
  {
    $this->login('root');
    $crawler = $this->client->request('GET', '/austral-admin/pages/new');

    $this->client->submit($crawler->filter('form[name="form"]')->form(['form[name]' => '']));

    self::assertResponseStatusCodeSame(422);
    self::assertSame(422, $this->templateParameters()->getStatus());
  }

  public function testTheStateOfARequestIsNotKeptForTheNextOne(): void
  {
    $this->login('root');
    $this->client->request('GET', '/austral-admin/pages');
    self::assertTrue($this->handler()->hasModule());

    $this->client->request('GET', '/austral-admin/');

    self::assertResponseIsSuccessful();
    self::assertFalse($this->handler()->hasModule(), 'The module of the previous request must be forgotten (kernel.reset).');
    self::assertNull($this->templateParameters()->getParameter('module'), 'The dashboard has no module.');
    self::assertNull($this->handler()->getResponse());
  }

  public function testTheResponseIsCompressedWithTheDefaultConfiguration(): void
  {
    $this->login('root');

    $this->client->request('GET', '/austral-admin/', [], [], ['HTTP_ACCEPT_ENCODING' => 'gzip']);

    self::assertResponseIsSuccessful();
    self::assertResponseHeaderSame('Content-Encoding', 'gzip');
  }

  private function handler(): AdminHandler
  {
    $handler = static::getContainer()->get(AdminHandler::class);
    self::assertInstanceOf(AdminHandler::class, $handler);

    return $handler;
  }

  private function templateParameters(): TemplateParameters
  {
    $templateParameters = static::getContainer()->get(TemplateParameters::class);
    self::assertInstanceOf(TemplateParameters::class, $templateParameters);

    return $templateParameters;
  }
}
