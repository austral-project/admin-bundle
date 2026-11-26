<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Event;

use Austral\AdminBundle\Module\Module;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Page Render Event.
 *
 * Dispatched before a page of the admin is rendered (AdminEvents::PAGE_RENDER): a listener changes the template
 * or adds variables. The page names its kind (dashboard, list, form, page, tiles, security...).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class PageRenderEvent extends Event
{
  /**
   * @param Request              $request
   * @param string               $page     Kind of page
   * @param string               $template
   * @param array<string, mixed> $vars
   * @param Module|null          $module
   */
  public function __construct(
    private readonly Request $request,
    private readonly string $page,
    private string $template,
    private array $vars,
    private readonly ?Module $module = null,
  ) {
  }

  /**
   * @return Request
   */
  public function getRequest(): Request
  {
    return $this->request;
  }

  /**
   * @return string
   */
  public function getPage(): string
  {
    return $this->page;
  }

  /**
   * @return Module|null
   */
  public function getModule(): ?Module
  {
    return $this->module;
  }

  /**
   * @return string
   */
  public function getTemplate(): string
  {
    return $this->template;
  }

  /**
   * @param string $template
   *
   * @return self
   */
  public function setTemplate(string $template): self
  {
    $this->template = $template;

    return $this;
  }

  /**
   * @return array<string, mixed>
   */
  public function getVars(): array
  {
    return $this->vars;
  }

  /**
   * @param array<string, mixed> $vars Added to the variables
   *
   * @return self
   */
  public function addVars(array $vars): self
  {
    $this->vars = array_replace($this->vars, $vars);

    return $this;
  }
}
