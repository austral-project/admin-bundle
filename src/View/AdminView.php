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

use Symfony\Component\HttpFoundation\Response;

/**
 * Austral Admin View.
 *
 * Page to render, returned by the services of the pages (ListPage, FormPage, DashboardPage, AccountPage) and given
 * to AdminHandler::view(), which adds the module and the breadcrumb, dispatches AdminEvents::PAGE_RENDER and fills
 * the template parameters.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final readonly class AdminView
{
  /**
   * __construct
   *
   * @param string               $page     name of the page (class of the body, PageRenderEvent::getPage())
   * @param string               $template
   * @param array<string, mixed> $vars
   * @param int                  $status   status code of the response
   * @param string|null          $last     last item of the breadcrumb (label of the object)
   *
   * @return void
   */
  public function __construct(
    public string $page,
    public string $template,
    public array $vars = [],
    public int $status = Response::HTTP_OK,
    public ?string $last = null,
  ) {
  }
}
