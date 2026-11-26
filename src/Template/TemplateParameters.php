<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Template;

use Austral\HttpBundle\Template\HttpTemplateParametersParameters;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Austral Admin Template Parameters.
 *
 * Template, variables and status code of the page of the current request. HttpAdminEventSubscriber adds the
 * variables shared by all the pages (user, language, module), the AdminHandler the template of the page and its
 * variables, and the controllers render them. Shared service, reset between two requests (kernel.reset).
 *
 * @extends HttpTemplateParametersParameters<object>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class TemplateParameters extends HttpTemplateParametersParameters implements ResetInterface
{
  /**
   * @var int
   */
  private int $status = Response::HTTP_OK;

  /**
   * getStatus
   * Status code of the response of the page.
   *
   * @return int
   */
  public function getStatus(): int
  {
    return $this->status;
  }

  /**
   * setStatus
   *
   * @param int $status
   *
   * @return $this
   */
  public function setStatus(int $status): self
  {
    $this->status = $status;

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function reset(): void
  {
    $this->parameters = [];
    $this->path = null;
    $this->name = 'default';
    $this->status = Response::HTTP_OK;
  }
}
