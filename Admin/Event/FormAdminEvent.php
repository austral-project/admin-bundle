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

use Austral\FormBundle\Mapper\FormMapper;

use Symfony\Component\Form\FormInterface;

/**
 * Austral Admin Event Form.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class FormAdminEvent extends AdminEvent
{

  CONST EVENT_START = "austral.admin.event.form.start";
  CONST EVENT_HANDLE_REQUEST_BEFORE = "austral.admin.event.form.handle_request.before";
  CONST EVENT_HANDLE_REQUEST_AFTER = "austral.admin.event.form.handle_request.after";
  CONST EVENT_AUSTRAL_FORM_VALIDATE = "austral.admin.event.form.validate.form";
  CONST EVENT_UPDATE_BEFORE = "austral.admin.event.form.update.before";
  CONST EVENT_UPDATE_AFTER = "austral.admin.event.form.update.after";
  CONST EVENT_END = "austral.admin.event.form.end";

  /**
   * @var FormMapper
   */
  private FormMapper $formMapper;

  /**
   * @var FormInterface
   */
  private FormInterface $form;

  /**
   * @var TemplateParametersInterface
   */
  private TemplateParametersInterface $templateParameters;

  /**
   * @var bool
   */
  private bool $valideForm;

  /**
   * FormAdminEvent constructor.
   * @param AdminHandler $adminHandler
   * @param TemplateParametersInterface $templateParameters
   * @param FormMapper $formMapper
   */
  public function __construct(
    AdminHandler $adminHandler,
    TemplateParametersInterface $templateParameters,
    FormMapper $formMapper)
  {
    parent::__construct($adminHandler);
    $this->formMapper = $formMapper;
    $this->templateParameters = $templateParameters;
  }

  /**
   * Get $this->formMapper
   * @return FormMapper
   */
  public function getFormMapper(): FormMapper
  {
    return $this->formMapper;
  }

  /**
   * Get formMapper
   * @return FormInterface
   */
  public function getForm(): FormInterface
  {
    return $this->form;
  }

  /**
   * Get formMapper
   *
   * @param FormInterface $form
   *
   * @return FormAdminEvent
   */
  public function setForm(FormInterface $form): FormAdminEvent
  {
    $this->form = $form;
    return $this;
  }

  /**
   * Get templateParameters
   * @return TemplateParametersInterface
   */
  public function getTemplateParameters(): TemplateParametersInterface
  {
    return $this->templateParameters;
  }

  /**
   * Get valideForm
   * @return bool
   */
  public function isValideForm(): bool
  {
    return $this->valideForm;
  }

  /**
   * @param bool $valideForm
   *
   * @return $this
   */
  public function setValideForm(bool $valideForm): FormAdminEvent
  {
    $this->valideForm = $valideForm;
    return $this;
  }

}