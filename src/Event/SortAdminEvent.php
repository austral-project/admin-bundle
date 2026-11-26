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
 * Austral Sort Admin Event.
 *
 * The entities whose position changed, before the positions are saved.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class SortAdminEvent extends AdminEvent
{
  /**
   * @param Module       $module
   * @param Request      $request
   * @param list<object> $objects In their new order
   * @param string       $field   The position field
   */
  public function __construct(Module $module, Request $request, private readonly array $objects, private readonly string $field)
  {
    parent::__construct($module, $request);
  }

  /**
   * @return list<object>
   */
  public function getObjects(): array
  {
    return $this->objects;
  }

  /**
   * @return string
   */
  public function getField(): string
  {
    return $this->field;
  }
}
