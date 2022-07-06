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

use Austral\FilterBundle\Mapper\FilterMapper;
use Austral\ListBundle\Mapper\ListMapper;

/**
 * Austral Filter Event Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface FilterEventInterface
{

  /**
   * Get objects
   * @return ListMapper
   */
  public function getListMapper(): ?ListMapper;

  /**
   * @param ?ListMapper $listMapper
   *
   * @return $this
   */
  public function setListMapper(ListMapper $listMapper): FilterEventInterface;

  /**
   * @return FilterMapper|null
   */
  public function getFilterMapper(): ?FilterMapper;

  /**
   * @param FilterMapper|null $filterMapper
   *
   * @return $this
   */
  public function setFilterMapper(?FilterMapper $filterMapper): FilterEventInterface;

}