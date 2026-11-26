<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Handler;

use Austral\AdminBundle\Event\AdminEventDispatcher;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Event\AdminHttpEvent;
use Austral\AdminBundle\Event\PageAdminEvent;
use Austral\AdminBundle\Event\PageRenderEvent;
use Austral\AdminBundle\Export\Exporter;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleDefinition;
use Austral\AdminBundle\Module\ModuleFactory;
use Austral\AdminBundle\Page\AccountPage;
use Austral\AdminBundle\Page\DashboardPage;
use Austral\AdminBundle\Page\EntityActions;
use Austral\AdminBundle\Page\EntityQuery;
use Austral\AdminBundle\Page\FormPage;
use Austral\AdminBundle\Page\ListConfigurator;
use Austral\AdminBundle\Page\ListPage;
use Austral\AdminBundle\Page\ListUrlStore;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\Security\CsrfGuard;
use Austral\AdminBundle\Template\TemplateParameters;
use Austral\AdminBundle\View\AdminView;
use Austral\AdminBundle\View\Breadcrumb;
use Austral\AdminBundle\View\Navigation;
use Austral\HttpBundle\Handler\HttpHandler;
use Austral\ToolsBundle\Services\Debug;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Austral Admin Handler.
 *
 * One method per page of the admin, called by AdminController and SecurityController. HttpAdminEventSubscriber
 * gives it the module of the route (already granted) and the template parameters. A method either fills the
 * template parameters with view(), or sets the response to return (redirection, JSON, export).
 * Shared service, reset between two requests (kernel.reset).
 *
 * @extends HttpHandler<object>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminHandler extends HttpHandler
{
  public const string TEMPLATE_TILES = '@AustralAdmin/module/tiles.html.twig';
  public const string TEMPLATE_PAGE = '@AustralAdmin/module/page.html.twig';

  /**
   * @var Module|null
   */
  private ?Module $module = null;

  /**
   * @var Response|null
   */
  private ?Response $response = null;

  /**
   * __construct
   *
   * @param EventDispatcherInterface $dispatcher
   * @param TranslatorInterface      $translator
   * @param RouterInterface          $router
   * @param RequestStack             $requestStack
   * @param string                   $environment
   * @param Debug|null               $debug
   * @param AdminEventDispatcher     $adminDispatcher
   * @param ModuleFactory            $factory
   * @param ListPage                 $listPage
   * @param FormPage                 $formPage
   * @param EntityActions            $entityActions
   * @param EntityQuery              $query
   * @param Exporter                 $exporter
   * @param DashboardPage            $dashboardPage
   * @param AccountPage              $accountPage
   * @param Navigation               $navigation
   * @param Breadcrumb               $breadcrumb
   * @param AdminUrlGenerator        $urls
   * @param ListUrlStore             $listUrls
   * @param CsrfGuard                $csrf
   *
   * @return void
   */
  public function __construct(
    EventDispatcherInterface $dispatcher,
    TranslatorInterface $translator,
    RouterInterface $router,
    RequestStack $requestStack,
    string $environment,
    ?Debug $debug,
    private readonly AdminEventDispatcher $adminDispatcher,
    private readonly ModuleFactory $factory,
    private readonly ListPage $listPage,
    private readonly FormPage $formPage,
    private readonly EntityActions $entityActions,
    private readonly EntityQuery $query,
    private readonly Exporter $exporter,
    private readonly DashboardPage $dashboardPage,
    private readonly AccountPage $accountPage,
    private readonly Navigation $navigation,
    private readonly Breadcrumb $breadcrumb,
    private readonly AdminUrlGenerator $urls,
    private readonly ListUrlStore $listUrls,
    private readonly CsrfGuard $csrf,
  ) {
    parent::__construct($dispatcher, $translator, $router, $requestStack, $environment, $debug);
  }

  /**
   * hasModule
   *
   * @return bool
   */
  public function hasModule(): bool
  {
    return null !== $this->module;
  }

  /**
   * getModule
   * Module of the route, resolved and granted by HttpAdminEventSubscriber.
   *
   * @return Module
   *
   * @throws \LogicException on a page without module (dashboard, my account, firewall)
   */
  public function getModule(): Module
  {
    return $this->module ?? throw new \LogicException('This page of the admin has no module: the route has no _austral_admin_module attribute.');
  }

  /**
   * setModule
   *
   * @param Module $module
   *
   * @return $this
   */
  public function setModule(Module $module): self
  {
    $this->module = $module;

    return $this;
  }

  /**
   * getResponse
   * Response set by the page (redirection, JSON, export); null when the template parameters are rendered.
   *
   * @return Response|null
   */
  public function getResponse(): ?Response
  {
    return $this->response;
  }

  /**
   * setResponse
   *
   * @param Response $response
   *
   * @return $this
   */
  public function setResponse(Response $response): self
  {
    $this->response = $response;

    return $this;
  }

  /**
   * view
   * Fills the template parameters with a page: its variables, the module and the breadcrumb, after
   * AdminEvents::PAGE_RENDER (an application or a bridge can change the template and add variables).
   *
   * @param AdminView $view
   *
   * @return void
   */
  public function view(AdminView $view): void
  {
    $vars = $view->vars + [
      'module' => $this->module,
      'page' => $view->page,
      'breadcrumb' => $this->breadcrumb->build($this->module, $view->last),
    ];
    $event = $this->adminDispatcher->dispatchGlobal(new PageRenderEvent($this->request(), $view->page, $view->template, $vars, $this->module), AdminEvents::PAGE_RENDER);

    $templateParameters = $this->templateParameters();
    $templateParameters->setStatus($view->status);
    $templateParameters->setPath($event->getTemplate());
    foreach ($event->getVars() as $key => $value)
    {
      $templateParameters->addParameters($key, $value);
    }
  }

  /**
   * dashboard
   *
   * @return void
   */
  public function dashboard(): void
  {
    $this->view($this->dashboardPage->build($this->request()));
  }

  /**
   * account
   * My account: form of the connected user.
   *
   * @return void
   */
  public function account(): void
  {
    $this->apply($this->accountPage->handle($this->request()));
  }

  /**
   * list
   *
   * @return void
   */
  public function list(): void
  {
    $this->view($this->listPage->build($this->getModule(), $this->request()));
  }

  /**
   * variants
   * Tiles of the variants of the next level (ex. one tile per domain). A level with one variant only (a site with
   * one domain) is skipped: redirection to its URL.
   *
   * @return void
   */
  public function variants(): void
  {
    $module = $this->getModule();
    $variants = $this->factory->variants($module);
    if (1 === \count($variants))
    {
      $this->setResponse(new RedirectResponse($this->urls->module($module->withVariants([...$module->getVariants(), $variants[0]]))));

      return;
    }

    $tiles = [];
    foreach ($variants as $variant)
    {
      $tiles[] = [
        'key' => $variant->key,
        'label' => $variant->name,
        'translate' => false,
        'picto' => $variant->picto ?? $module->getDefinition()->getPicto(),
        'image' => $variant->image,
        'url' => $this->urls->module($module->withVariants([...$module->getVariants(), $variant])),
        'attributes' => $variant->attributes,
      ];
    }

    $this->view(new AdminView('variants', self::TEMPLATE_TILES, ['tiles' => $tiles]));
  }

  /**
   * container
   * Tiles of the children of a container module.
   *
   * @return void
   */
  public function container(): void
  {
    $tiles = array_map(static fn (array $item): array => [
      'key' => $item['key'],
      'label' => $item['label'],
      'translate' => true,
      'picto' => $item['picto_tile'] ?? $item['picto'],
      'image' => null,
      'url' => $item['url'],
      'attributes' => [],
    ], $this->navigation->children($this->getModule()->getDefinition()));

    $this->view(new AdminView('container', self::TEMPLATE_TILES, ['tiles' => $tiles]));
  }

  /**
   * create
   *
   * @return void
   */
  public function create(): void
  {
    $this->apply($this->formPage->handle($this->getModule(), $this->request()));
  }

  /**
   * edit
   *
   * @return void
   */
  public function edit(): void
  {
    $this->apply($this->formPage->handle($this->getModule(), $this->request(), $this->id()));
  }

  /**
   * duplicate
   *
   * @return void
   */
  public function duplicate(): void
  {
    $this->setResponse($this->entityActions->duplicate($this->getModule(), $this->request(), $this->id()));
  }

  /**
   * delete
   *
   * @return void
   */
  public function delete(): void
  {
    $this->setResponse($this->entityActions->delete($this->getModule(), $this->request(), $this->id()));
  }

  /**
   * truncate
   *
   * @return void
   */
  public function truncate(): void
  {
    $this->setResponse($this->entityActions->truncate($this->getModule(), $this->request()));
  }

  /**
   * toggle
   *
   * @return void
   */
  public function toggle(): void
  {
    $request = $this->request();
    $this->setResponse($this->entityActions->toggle($this->getModule(), $request, $this->id(), (string) $request->attributes->get('field')));
  }

  /**
   * sort
   *
   * @return void
   */
  public function sort(): void
  {
    $this->setResponse($this->entityActions->sort($this->getModule(), $this->request()));
  }

  /**
   * export
   *
   * @return void
   */
  public function export(): void
  {
    $request = $this->request();
    $this->setResponse($this->exporter->export($this->getModule(), $request, (string) $request->attributes->get('format')));
  }

  /**
   * action
   * Extra action of an entity module: a method of the Admin, which returns a response, a template, or nothing
   * (back to the list).
   *
   * @return void
   */
  public function action(): void
  {
    $module = $this->getModule();
    $request = $this->request();
    $action = (string) $request->attributes->get(AdminRouteLoader::ATTRIBUTE_ACTION);
    if ($request->isMethod('POST'))
    {
      $this->csrf->check($request);
    }

    $object = $this->query->find($module, $request, $this->id());
    $event = new PageAdminEvent($module, $request, $action, $object);
    $module->getAdmin()->callAction($module->getDefinition()->getExtendActions()[$action], $event);
    if (null !== ($response = $event->getResponse()))
    {
      $this->setResponse($response);

      return;
    }
    if (null !== $event->getTemplate())
    {
      $this->view(new AdminView('action', $event->getTemplate(), $event->getVars() + ['object' => $object, 'action' => $action], Response::HTTP_OK, ListConfigurator::label($object)));

      return;
    }

    $this->setResponse(new RedirectResponse($this->listUrls->get($module, $request)));
  }

  /**
   * page
   * Action of a page module: a method of the Admin.
   *
   * @return void
   */
  public function page(): void
  {
    $module = $this->getModule();
    $request = $this->request();
    $action = (string) $request->attributes->get(AdminRouteLoader::ATTRIBUTE_ACTION, ModuleDefinition::PAGE_INDEX);
    $event = new PageAdminEvent($module, $request, $action);
    $module->getAdmin()->callAction($module->getDefinition()->getActions()[$action], $event);
    if (null !== ($response = $event->getResponse()))
    {
      $this->setResponse($response);

      return;
    }

    $this->view(new AdminView('page', $event->getTemplate() ?? self::TEMPLATE_PAGE, $event->getVars() + ['action' => $action]));
  }

  /**
   * {@inheritDoc}
   */
  public function reset(): void
  {
    parent::reset();
    $this->module = null;
    $this->response = null;
  }

  /**
   * apply
   *
   * @param Response|AdminView $result
   *
   * @return void
   */
  private function apply(Response|AdminView $result): void
  {
    if ($result instanceof Response)
    {
      $this->setResponse($result);

      return;
    }

    $this->view($result);
  }

  /**
   * templateParameters
   *
   * @return TemplateParameters
   *
   * @throws \LogicException when HttpAdminEventSubscriber did not prepare the handler
   */
  private function templateParameters(): TemplateParameters
  {
    $templateParameters = $this->getTemplateParameters();
    if (!$templateParameters instanceof TemplateParameters)
    {
      throw new \LogicException('The template parameters of the admin are not set: the route must have the "_austral_http_event" attribute set to '.AdminHttpEvent::class.'.');
    }

    return $templateParameters;
  }

  /**
   * request
   *
   * @return Request
   *
   * @throws \LogicException outside of a request
   */
  private function request(): Request
  {
    return $this->getRequest() ?? throw new \LogicException('The admin handler is used outside of a request.');
  }

  /**
   * id
   *
   * @return string
   */
  private function id(): string
  {
    return (string) $this->request()->attributes->get('id');
  }
}
