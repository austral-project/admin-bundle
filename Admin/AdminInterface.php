<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Austral\AdminBundle\Admin;

use Austral\AdminBundle\Admin\Event\AdminEventInterface;
use Austral\EntityBundle\Entity\EntityInterface;

/**
 * Austral Admin  Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface AdminInterface
{

  /**
   * @return array
   */
  public function getEvents(): array;

  /**
   * @param $container
   *
   * @return $this
   */
  public function setContainer($container): AdminInterface;

  /**
   * @param $translator
   *
   * @return $this
   */
  public function setTranslator($translator): AdminInterface;

  /**
   * @param string $eventName
   * @param AdminEventInterface $managerEvent
   */
  public function dispatch(string $eventName, AdminEventInterface $managerEvent);

  /**
   * @param EntityInterface $object
   *
   * @return $this
   */
  public function setObject(EntityInterface $object): AdminInterface;

}