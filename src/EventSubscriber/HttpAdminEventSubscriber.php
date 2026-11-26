<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\EventSubscriber;

use Austral\AdminBundle\Event\AdminHttpEvent;
use Austral\AdminBundle\Handler\AdminHandler;
use Austral\AdminBundle\Module\ModuleResolver;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Template\TemplateParameters;
use Austral\HttpBundle\Event\Interfaces\HttpEventInterface;
use Austral\HttpBundle\EventSubscriber\HttpEventSubscriber;
use Austral\SecurityBundle\Entity\Interfaces\UserInterface;
use Austral\ToolsBundle\Configuration\ConfigurationInterface;
use Austral\ToolsBundle\Services\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\LocaleSwitcher;

/**
 * Austral Http Admin EventSubscriber.
 *
 * Prepares the pages of the admin from the HTTP events of http-bundle (AdminHttpEvent only):
 * - austral.event.http.request.initialise: compression of the response (compression_gzip.admin, else default);
 * - austral.event.http.request (after the firewall): language of the interface, template parameters shared by all
 *   the pages (user, language), module of the route resolved and granted by ModuleResolver, then the AdminHandler
 *   given to the controller by the HttpListener.
 * The pages without module (dashboard, my account, pages of the firewall) get the handler and the template
 * parameters too. A bridge adds its own template parameters with a lower priority on the same event
 * ($httpEvent->getHandler()->getTemplateParameters()).
 *
 * @extends HttpEventSubscriber<object>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class HttpAdminEventSubscriber extends HttpEventSubscriber
{
  public const int PRIORITY = 1024;

  /**
   * __construct
   *
   * @param ConfigurationInterface $configuration      configuration of http-bundle (compression_gzip)
   * @param Debug                  $debug
   * @param ModuleResolver         $resolver
   * @param AdminHandler           $handler
   * @param TemplateParameters     $templateParameters
   * @param TokenStorageInterface  $tokenStorage
   * @param LocaleSwitcher|null    $localeSwitcher
   * @param list<string>           $interfaceLanguages languages of the interface, the first by default
   * @param list<string>           $contentLanguages   languages of the content
   *
   * @return void
   */
  public function __construct(
    ConfigurationInterface $configuration,
    Debug $debug,
    private readonly ModuleResolver $resolver,
    private readonly AdminHandler $handler,
    private readonly TemplateParameters $templateParameters,
    private readonly TokenStorageInterface $tokenStorage,
    private readonly ?LocaleSwitcher $localeSwitcher = null,
    private readonly array $interfaceLanguages = ['en', 'fr'],
    private readonly array $contentLanguages = [],
  ) {
    parent::__construct($configuration, $debug);
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array
  {
    return [
      HttpEventInterface::EVENT_AUSTRAL_HTTP_REQUEST_INITIALISE => ['onRequestInitialise', self::PRIORITY],
      HttpEventInterface::EVENT_AUSTRAL_HTTP_REQUEST => ['onRequest', self::PRIORITY],
      HttpEventInterface::EVENT_AUSTRAL_HTTP_CONTROLLER => ['onController', self::PRIORITY],
      HttpEventInterface::EVENT_AUSTRAL_HTTP_RESPONSE => ['onResponse', self::PRIORITY],
      HttpEventInterface::EVENT_AUSTRAL_HTTP_EXCEPTION => ['onException', self::PRIORITY],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function onRequestInitialise(HttpEventInterface $httpEvent): void
  {
    if (!$httpEvent instanceof AdminHttpEvent)
    {
      return;
    }

    $httpEvent->getHttpRequest()?->setCompressionGzipEnabled((bool) $this->configuration->get('compression_gzip.admin', $this->configuration->get('compression_gzip.default', true)));
  }

  /**
   * {@inheritDoc}
   */
  public function onRequest(HttpEventInterface $httpEvent): void
  {
    $request = $httpEvent->getKernelEvent()?->getRequest();
    if (!$httpEvent instanceof AdminHttpEvent || null === $request)
    {
      return;
    }

    $this->debug->stopWatchStart('austral.admin.http.request', 'austral.admin.event.subscriber');

    $user = $this->tokenStorage->getToken()?->getUser();
    $user = $user instanceof UserInterface ? $user : null;
    $language = $this->interfaceLanguage($request, $user);
    $request->setLocale($language);
    $this->localeSwitcher?->setLocale($language);
    $httpEvent->getHttpRequest()?->setLanguage($language);

    $this->templateParameters
      ->addParameters('user', $user)
      ->addParameters('language', [
        'interface' => $language,
        'current' => $this->contentLanguages[0] ?? $language,
        'default' => $this->contentLanguages[0] ?? $language,
        'list' => $this->contentLanguages,
      ]);
    $this->handler->setTemplateParameters($this->templateParameters);

    if ($request->attributes->has(AdminRouteLoader::ATTRIBUTE_MODULE))
    {
      $module = $this->resolver->resolve($request);
      $this->handler->setModule($module);
      $this->templateParameters->addParameters('module', $module);
      if (null !== $module->getLanguage())
      {
        $this->templateParameters->addParameters('language', ['current' => $module->getLanguage()], true);
      }
    }

    $httpEvent->setHandler($this->handler);

    $this->debug->stopWatchStop('austral.admin.http.request');
  }

  /**
   * {@inheritDoc}
   */
  public function onController(HttpEventInterface $httpEvent): void
  {
  }

  /**
   * {@inheritDoc}
   */
  public function onResponse(HttpEventInterface $httpEvent): void
  {
  }

  /**
   * {@inheritDoc}
   */
  public function onException(HttpEventInterface $httpEvent): void
  {
  }

  /**
   * interfaceLanguage
   * Language of the connected user, else the preferred language of the browser, else the first language.
   *
   * @param Request            $request
   * @param UserInterface|null $user
   *
   * @return string
   */
  private function interfaceLanguage(Request $request, ?UserInterface $user): string
  {
    $languages = $this->interfaceLanguages ?: [$request->getLocale()];
    $language = $user?->getLanguage();
    if (null === $language || !\in_array($language, $languages, true))
    {
      $language = $request->getPreferredLanguage($languages) ?? $languages[0];
    }

    return $language;
  }
}
