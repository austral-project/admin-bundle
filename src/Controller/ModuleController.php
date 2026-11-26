<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Controller;

use Austral\AdminBundle\Event\PageAdminEvent;
use Austral\AdminBundle\Export\Exporter;
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Module\ModuleDefinition;
use Austral\AdminBundle\Module\ModuleResolver;
use Austral\AdminBundle\Page\EntityActions;
use Austral\AdminBundle\Page\EntityQuery;
use Austral\AdminBundle\Page\FormPage;
use Austral\AdminBundle\Page\ListConfigurator;
use Austral\AdminBundle\Page\ListPage;
use Austral\AdminBundle\Page\ListUrlStore;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\Security\CsrfGuard;
use Austral\AdminBundle\View\AdminRenderer;
use Austral\AdminBundle\View\Navigation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Austral Module Controller.
 *
 * The actions of the modules (routes of AdminRouteLoader). The module comes from the route, its rights are checked
 * by ModuleResolver.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ModuleController
{
  public const string TEMPLATE_TILES = '@AustralAdmin/module/tiles.html.twig';
  public const string TEMPLATE_PAGE = '@AustralAdmin/module/page.html.twig';

  /**
   * @param ModuleResolver    $resolver
   * @param ListPage          $listPage
   * @param FormPage          $formPage
   * @param EntityActions     $entityActions
   * @param EntityQuery       $query
   * @param Exporter          $exporter
   * @param Navigation        $navigation
   * @param AdminUrlGenerator $urls
   * @param ListUrlStore      $listUrls
   * @param CsrfGuard         $csrf
   * @param AdminRenderer     $renderer
   */
  public function __construct(
    private readonly ModuleResolver $resolver,
    private readonly ListPage $listPage,
    private readonly FormPage $formPage,
    private readonly EntityActions $entityActions,
    private readonly EntityQuery $query,
    private readonly Exporter $exporter,
    private readonly Navigation $navigation,
    private readonly AdminUrlGenerator $urls,
    private readonly ListUrlStore $listUrls,
    private readonly CsrfGuard $csrf,
    private readonly AdminRenderer $renderer,
  ) {
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function list(Request $request): Response
  {
    return $this->listPage->render($this->resolver->resolve($request, ModuleAction::LIST), $request);
  }

  /**
   * Tiles of the variants of the next level.
   *
   * @param Request $request
   *
   * @return Response
   */
  public function variants(Request $request): Response
  {
    $module = $this->resolver->resolve($request, ModuleAction::LIST);
    $tiles = [];
    foreach ($this->resolver->getFactory()->variants($module) as $variant)
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

    return $this->renderer->render($request, 'variants', self::TEMPLATE_TILES, ['tiles' => $tiles], $module);
  }

  /**
   * Tiles of the children of a container module.
   *
   * @param Request $request
   *
   * @return Response
   */
  public function container(Request $request): Response
  {
    $module = $this->resolver->resolve($request, ModuleAction::LIST);
    $tiles = array_map(static fn (array $item): array => [
      'key' => $item['key'],
      'label' => $item['label'],
      'translate' => true,
      'picto' => $item['picto_tile'] ?? $item['picto'],
      'image' => null,
      'url' => $item['url'],
      'attributes' => [],
    ], $this->navigation->children($module->getDefinition()));

    return $this->renderer->render($request, 'container', self::TEMPLATE_TILES, ['tiles' => $tiles], $module);
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function create(Request $request): Response
  {
    return $this->formPage->render($this->resolver->resolve($request, ModuleAction::CREATE), $request);
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function edit(Request $request): Response
  {
    return $this->formPage->render($this->resolver->resolve($request, ModuleAction::EDIT), $request, self::id($request));
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function duplicate(Request $request): Response
  {
    return $this->entityActions->duplicate($this->resolver->resolve($request, ModuleAction::DUPLICATE), $request, self::id($request));
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function delete(Request $request): Response
  {
    return $this->entityActions->delete($this->resolver->resolve($request, ModuleAction::DELETE), $request, self::id($request));
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function truncate(Request $request): Response
  {
    return $this->entityActions->truncate($this->resolver->resolve($request, ModuleAction::TRUNCATE), $request);
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function toggle(Request $request): Response
  {
    return $this->entityActions->toggle($this->resolver->resolve($request, ModuleAction::TOGGLE), $request, self::id($request), (string) $request->attributes->get('field'));
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function sort(Request $request): Response
  {
    return $this->entityActions->sort($this->resolver->resolve($request, ModuleAction::SORT), $request);
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function export(Request $request): Response
  {
    return $this->exporter->export($this->resolver->resolve($request, ModuleAction::EXPORT), $request, (string) $request->attributes->get('format'));
  }

  /**
   * Extra action of an entity module ("extendActions"): a method of the Admin, given the entity. A POST needs the
   * CSRF token of the admin.
   *
   * @param Request $request
   *
   * @return Response
   */
  public function action(Request $request): Response
  {
    $module = $this->resolver->resolve($request);
    $action = (string) $request->attributes->get(AdminRouteLoader::ATTRIBUTE_ACTION);
    if ($request->isMethod('POST'))
    {
      $this->csrf->check($request);
    }
    $object = $this->query->find($module, $request, self::id($request));
    $event = new PageAdminEvent($module, $request, $action, $object);
    $module->getAdmin()->callAction($module->getDefinition()->getExtendActions()[$action], $event);
    if (null !== ($response = $event->getResponse()))
    {
      return $response;
    }
    if (null !== $event->getTemplate())
    {
      return $this->renderer->render($request, 'action', $event->getTemplate(), $event->getVars() + ['object' => $object, 'action' => $action], $module, Response::HTTP_OK, ListConfigurator::label($object));
    }

    return new RedirectResponse($this->listUrls->get($module, $request));
  }

  /**
   * Action of a page module: a method of the Admin.
   *
   * @param Request $request
   *
   * @return Response
   */
  public function page(Request $request): Response
  {
    $module = $this->resolver->resolve($request);
    $action = (string) $request->attributes->get(AdminRouteLoader::ATTRIBUTE_ACTION, ModuleDefinition::PAGE_INDEX);
    $event = new PageAdminEvent($module, $request, $action);
    $module->getAdmin()->callAction($module->getDefinition()->getActions()[$action], $event);
    if (null !== ($response = $event->getResponse()))
    {
      return $response;
    }

    return $this->renderer->render($request, 'page', $event->getTemplate() ?? self::TEMPLATE_PAGE, $event->getVars() + ['action' => $action], $module);
  }

  /**
   * @param Request $request
   *
   * @return string
   */
  private static function id(Request $request): string
  {
    return (string) $request->attributes->get('id');
  }
}
