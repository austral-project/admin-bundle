<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\View;

use Austral\AdminBundle\Event\AdminEventDispatcher;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Event\PageRenderEvent;
use Austral\AdminBundle\Module\Module;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Austral Admin Renderer.
 *
 * Renders a page of the admin: the variables of the page, the module and the breadcrumb, after
 * AdminEvents::PAGE_RENDER.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminRenderer
{
  /**
   * @param Environment          $twig
   * @param AdminEventDispatcher $dispatcher
   * @param Breadcrumb           $breadcrumb
   */
  public function __construct(
    private readonly Environment $twig,
    private readonly AdminEventDispatcher $dispatcher,
    private readonly Breadcrumb $breadcrumb,
  ) {
  }

  /**
   * @param Request              $request
   * @param string               $page     Kind of page, given to the listeners
   * @param string               $template
   * @param array<string, mixed> $vars
   * @param Module|null          $module
   * @param int                  $status
   * @param string|null          $last     Last item of the breadcrumb (the entity)
   *
   * @return Response
   */
  public function render(Request $request, string $page, string $template, array $vars = [], ?Module $module = null, int $status = Response::HTTP_OK, ?string $last = null): Response
  {
    $vars += [
      'module' => $module,
      'page' => $page,
      'breadcrumb' => $this->breadcrumb->build($module, $last),
    ];
    $event = $this->dispatcher->dispatchGlobal(new PageRenderEvent($request, $page, $template, $vars, $module), AdminEvents::PAGE_RENDER);

    return new Response($this->twig->render($event->getTemplate(), $event->getVars()), $status);
  }
}
