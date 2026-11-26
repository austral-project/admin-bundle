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
use Austral\FilterBundle\Mapper\FilterMapper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral Filter Admin Event.
 *
 * Filters of the list of an entity module. A group named as a section filters this section, the group
 * "default" filters the sections without a group of their name.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class FilterAdminEvent extends AdminEvent
{
  /**
   * @param Module       $module
   * @param Request      $request
   * @param FilterMapper $mapper
   */
  public function __construct(Module $module, Request $request, private readonly FilterMapper $mapper)
  {
    parent::__construct($module, $request);
  }

  /**
   * @return FilterMapper
   */
  public function getMapper(): FilterMapper
  {
    return $this->mapper;
  }

  /**
   * @return FilterMapper Alias of getMapper(), name of the 3.1
   */
  public function getFilterMapper(): FilterMapper
  {
    return $this->mapper;
  }
}
