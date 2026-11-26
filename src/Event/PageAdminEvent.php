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
use Symfony\Component\HttpFoundation\Response;

/**
 * Austral Page Admin Event.
 *
 * Given to the method of an action of a page module, or of an extra action of an entity module (with the
 * entity). The method sets a response, or a template and its variables; without either, the action of a page
 * renders the default page template and an extra action goes back to the list.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class PageAdminEvent extends AdminEvent
{
  /**
   * @var array<string, mixed>
   */
  private array $vars = [];

  private ?string $template = null;

  private ?Response $response = null;

  /**
   * @param Module      $module
   * @param Request     $request
   * @param string      $action
   * @param object|null $object  The entity of an extra action
   */
  public function __construct(Module $module, Request $request, private readonly string $action, private readonly ?object $object = null)
  {
    parent::__construct($module, $request);
  }

  /**
   * @return string
   */
  public function getAction(): string
  {
    return $this->action;
  }

  /**
   * @return object|null
   */
  public function getObject(): ?object
  {
    return $this->object;
  }

  /**
   * @return string|null
   */
  public function getTemplate(): ?string
  {
    return $this->template;
  }

  /**
   * @param string|null $template
   *
   * @return self
   */
  public function setTemplate(?string $template): self
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
   * @param array<string, mixed> $vars Added to the variables already set
   *
   * @return self
   */
  public function addVars(array $vars): self
  {
    $this->vars = array_replace($this->vars, $vars);

    return $this;
  }

  /**
   * @return Response|null
   */
  public function getResponse(): ?Response
  {
    return $this->response;
  }

  /**
   * @param Response|null $response
   *
   * @return self
   */
  public function setResponse(?Response $response): self
  {
    $this->response = $response;

    return $this;
  }
}
