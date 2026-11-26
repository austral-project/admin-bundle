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

use Austral\AdminBundle\Event\AdminHttpEvent;
use Austral\AdminBundle\Handler\AdminHandler;
use Austral\AdminBundle\Template\TemplateParameters;
use Austral\AdminBundle\View\AdminView;
use Austral\HttpBundle\Controller\HttpController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Austral Admin Controller Abstract.
 *
 * Base of the controllers of the admin. The HttpListener of http-bundle gives them the AdminHandler prepared by
 * HttpAdminEventSubscriber, and its template parameters.
 *
 * @extends HttpController<object>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
abstract class AbstractAdminController extends HttpController
{
  /**
   * handler
   *
   * @return AdminHandler
   *
   * @throws \LogicException when the route has not the AdminHttpEvent
   */
  protected function handler(): AdminHandler
  {
    if (!$this->handlerManager instanceof AdminHandler)
    {
      throw new \LogicException(\sprintf('The admin handler is not set: the route must have the "_austral_http_event" attribute set to %s.', AdminHttpEvent::class));
    }

    return $this->handlerManager;
  }

  /**
   * respond
   * Response set by the handler, else the page of the template parameters.
   *
   * @return Response
   */
  protected function respond(): Response
  {
    return $this->handler()->getResponse() ?? $this->renderTemplateParameters();
  }

  /**
   * renderPage
   *
   * @param AdminView $view
   *
   * @return Response
   */
  protected function renderPage(AdminView $view): Response
  {
    $this->handler()->view($view);

    return $this->renderTemplateParameters();
  }

  /**
   * renderTemplateParameters
   *
   * @return Response
   *
   * @throws \LogicException when no template is set
   */
  private function renderTemplateParameters(): Response
  {
    $templateParameters = $this->handler()->getTemplateParameters();
    $path = $templateParameters?->getPath();
    if (!$templateParameters instanceof TemplateParameters || null === $path)
    {
      throw new \LogicException('The page of the admin has no template: the handler must call view() or set a response.');
    }

    return $this->render($path, $templateParameters->getParameters(), new Response(status: $templateParameters->getStatus()));
  }
}
