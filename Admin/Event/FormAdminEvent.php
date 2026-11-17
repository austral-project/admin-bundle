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
 * @template T of object
 *
 * @extends AdminEvent<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class FormAdminEvent extends AdminEvent
{

  /** @deprecated  */
  CONST string EVENT_START = "austral.admin.event.form.start";

  /** @deprecated  */
  CONST string EVENT_HANDLE_REQUEST_BEFORE = "austral.admin.event.form.handle_request.before";

  /** @deprecated  */
  CONST string EVENT_HANDLE_REQUEST_AFTER = "austral.admin.event.form.handle_request.after";

  /** @deprecated  */
  CONST string EVENT_AUSTRAL_FORM_VALIDATE = "austral.admin.event.form.validate.form";

  /** @deprecated  */
  CONST string EVENT_UPDATE_BEFORE = "austral.admin.event.form.update.before";

  /** @deprecated  */
  CONST string EVENT_UPDATE_AFTER = "austral.admin.event.form.update.after";

  /** @deprecated  */
  CONST string EVENT_FLUSH_AFTER = "austral.admin.event.form.flush.after";

  /** @deprecated  */
  CONST string EVENT_END = "austral.admin.event.form.end";


  /**
   * @var FormMapper<T>
   */
  private FormMapper $formMapper;

  /**
   * @var FormInterface|null
   */
  private ?FormInterface $form = null;

  /**
   * @var TemplateParametersInterface
   */
  private TemplateParametersInterface $templateParameters;

  /**
   * @var bool
   */
  private bool $valideForm = false;

  /**
   * @param AdminHandler<T> $adminHandler
   * @param TemplateParametersInterface $templateParameters
   * @param FormMapper<T> $formMapper
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
   * @return FormMapper<T>
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
    if($this->form === null){
      throw new \RuntimeException('Form is not initialized');
    }
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