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

/**
 * Austral Admin  Module Interface.
 * @template T of object
 *
 * @extends AdminInterface<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface AdminModuleInterface extends AdminInterface
{

  /**
   * @param ListAdminEvent<T> $listAdminEvent
   */
  public function configureListMapper(ListAdminEvent $listAdminEvent): void;

  /**
   * @param FormAdminEvent<T> $formAdminEvent
   *
   * @throws \Exception
   */
  public function configureFormMapper(FormAdminEvent $formAdminEvent): void;

}