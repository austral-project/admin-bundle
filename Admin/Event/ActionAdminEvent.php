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

use Austral\AdminBundle\Handler\AdminHandler;
use Austral\AdminBundle\Template\Interfaces\TemplateParametersInterface;
use Austral\EntityBundle\Entity\EntityInterface;

/**
 * Austral Admin Event Action.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ActionAdminEvent extends AdminEvent
{

  CONST EVENT_START = "austral.admin.event.action.start";
  CONST EVENT_END = "austral.admin.event.action.end";

  /**
   * @var EntityInterface|null
   */
  private ?EntityInterface $object;

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
   * FormAdminEvent constructor.
   *
   * @param AdminHandler $adminHandler
   * @param TemplateParametersInterface $templateParameters
   * @param string $actionKey
   * @param EntityInterface|null $object
   */
  public function __construct(AdminHandler $adminHandler, TemplateParametersInterface $templateParameters, string $actionKey, ?EntityInterface $object = null)
  {
    parent::__construct($adminHandler);
    $this->templateParameters = $templateParameters;
    $this->actionKey = $actionKey;
    $this->object = $object;
  }

  /**
   * Get object
   * @return EntityInterface|null
   */
  public function getObject(): ?EntityInterface
  {
    return $this->object;
  }

  /**
   * @param EntityInterface $object
   *
   * @return ActionAdminEvent
   */
  public function setObject(EntityInterface $object): ActionAdminEvent
  {
    $this->object = $object;
    return $this;
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
   * @return ActionAdminEvent
   */
  public function setRedirecturl(?string $redirectUrl): ActionAdminEvent
  {
    $this->redirectUrl = $redirectUrl;
    return $this;
  }

}