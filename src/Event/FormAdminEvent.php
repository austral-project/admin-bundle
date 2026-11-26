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
use Austral\FormBundle\Mapper\FormMapper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral Form Admin Event.
 *
 * The form of an entity of a module: its configuration (AdminEvents::FORM_CONFIGURE), then its save
 * (FORM_SAVE_BEFORE, FORM_SAVE_AFTER, with the form).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class FormAdminEvent extends AdminEvent
{
  /**
   * @param Module                    $module
   * @param Request                   $request
   * @param FormMapper                $mapper
   * @param object                    $object
   * @param bool                      $create  True for a new entity
   * @param FormInterface<mixed>|null $form
   */
  public function __construct(
    Module $module,
    Request $request,
    private readonly FormMapper $mapper,
    private readonly object $object,
    private readonly bool $create,
    private readonly ?FormInterface $form = null,
  ) {
    parent::__construct($module, $request);
  }

  /**
   * @return FormMapper
   */
  public function getMapper(): FormMapper
  {
    return $this->mapper;
  }

  /**
   * @return FormMapper Alias of getMapper(), name of the 3.1
   */
  public function getFormMapper(): FormMapper
  {
    return $this->mapper;
  }

  /**
   * @return object
   */
  public function getObject(): object
  {
    return $this->object;
  }

  /**
   * @return bool
   */
  public function isCreate(): bool
  {
    return $this->create;
  }

  /**
   * @return FormInterface<mixed>|null Null at the configuration
   */
  public function getForm(): ?FormInterface
  {
    return $this->form;
  }
}
