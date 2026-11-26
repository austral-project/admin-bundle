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

/**
 * Austral Object Admin Event.
 *
 * An action on an entity of a module: delete, duplicate (with the source), toggle (with the field and the value).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ObjectAdminEvent extends AdminEvent
{
  /**
   * @param Module      $module
   * @param Request     $request
   * @param object      $object
   * @param object|null $source  The entity copied (duplicate)
   * @param string|null $field   The field changed (toggle)
   * @param mixed       $value   The new value (toggle)
   */
  public function __construct(
    Module $module,
    Request $request,
    private readonly object $object,
    private readonly ?object $source = null,
    private readonly ?string $field = null,
    private readonly mixed $value = null,
  ) {
    parent::__construct($module, $request);
  }

  /**
   * @return object
   */
  public function getObject(): object
  {
    return $this->object;
  }

  /**
   * @return object|null
   */
  public function getSource(): ?object
  {
    return $this->source;
  }

  /**
   * @return string|null
   */
  public function getField(): ?string
  {
    return $this->field;
  }

  /**
   * @return mixed
   */
  public function getValue(): mixed
  {
    return $this->value;
  }
}
