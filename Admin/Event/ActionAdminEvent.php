<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Admin\Event;

use AllowDynamicProperties;
use Austral\AdminBundle\Handler\AdminHandler;
use Austral\AdminBundle\Template\Interfaces\TemplateParametersInterface;
use Austral\Contracts\Entity\EntityInterface;

/**
 * Austral Admin Event Action.
 * @template T of object
 *
 * @extends AdminEvent<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ActionAdminEvent extends AdminEvent
{

  /** @deprecated */
  CONST string EVENT_START = "austral.admin.event.action.start";

  /** @deprecated */
  CONST string EVENT_END = "austral.admin.event.action.end";

  /**
   * @var TemplateParametersInterface
   */
  private TemplateParametersInterface $templateParameters;

  /**
   * @var string
   */
  private string $actionKey;

  /**
   * @var string|null
   */
  private ?string $redirectUrl = null;

  /**
   * @param AdminHandler<T> $adminHandler
   * @param TemplateParametersInterface $templateParameters
   * @param string $actionKey
   * @param EntityInterface<T>|null $object
   */
  public function __construct(AdminHandler $adminHandler, TemplateParametersInterface $templateParameters, string $actionKey, ?EntityInterface $object = null)
  {
    parent::__construct($adminHandler);
    $this->templateParameters = $templateParameters;
    $this->actionKey = $actionKey;
    $this->object = $object;
  }

  /**
   * @return TemplateParametersInterface
   */
  public function getTemplateParameters(): TemplateParametersInterface
  {
    return $this->templateParameters;
  }

  /**
   * @return string
   */
  public function getActionKey(): string
  {
    return $this->actionKey;
  }

  /**
   * @return string|null
   */
  public function getRedirectUrl(): ?string
  {
    return $this->redirectUrl;
  }

  /**
   * @param string|null $redirectUrl
   *
   * @return $this
   */
  public function setRedirectUrl(?string $redirectUrl): self
  {
    $this->redirectUrl = $redirectUrl;
    return $this;
  }

}