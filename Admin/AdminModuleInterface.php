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

use Austral\AdminBundle\Admin\Event\FormAdminEvent;
use Austral\AdminBundle\Admin\Event\ListAdminEvent;
use Austral\EntityBundle\Entity\EntityInterface;

/**
 * Austral Admin  Module Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface AdminModuleInterface
{

  /**
   * @param ListAdminEvent $listAdminEvent
   */
  public function configureListMapper(ListAdminEvent $listAdminEvent);

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @throws \Exception
   */
  public function configureFormMapper(FormAdminEvent $formAdminEvent);

}