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
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface FilterEventInterface
{

  /**
   * @return ListMapper<T>|null
   */
  public function getListMapper(): ?ListMapper;

  /**
   * @param ListMapper<T> $listMapper
   *
   * @return $this
   */
  public function setListMapper(ListMapper $listMapper): self;

  /**
   * @return FilterMapper<T>|null
   */
  public function getFilterMapper(): ?FilterMapper;

  /**
   * @param FilterMapper<T> $filterMapper
   *
   * @return $this
   */
  public function setFilterMapper(FilterMapper $filterMapper): self;

}