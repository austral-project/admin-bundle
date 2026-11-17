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
use Austral\AdminBundle\Handler\Interfaces\AdminHandlerInterface;
use Austral\AdminBundle\Module\Module;
use Austral\Contracts\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral Admin Event Interface.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface AdminEventInterface
{

  CONST string ADMIN_EVENT_ACTION_START = "austral.admin.event.action.start";
  CONST string ADMIN_EVENT_ACTION_END = "austral.admin.event.action.end";

  CONST string ADMIN_EVENT_CHANGE_VALUE_START = "austral.admin.event.change_value.start";
  CONST string ADMIN_EVENT_CHANGE_VALUE_END = "austral.admin.event.change_value.end";

  CONST string ADMIN_EVENT_DELETE_START = "austral.admin.event.delete.start";
  CONST string ADMIN_EVENT_DELETE_END = "austral.admin.event.delete.end";

  CONST string ADMIN_EVENT_DOWNLOAD_START = "austral.admin.event.download.start";
  CONST string ADMIN_EVENT_DOWNLOAD_END = "austral.admin.event.download.end";

  CONST string ADMIN_EVENT_DUPLICATE_START = "austral.admin.event.duplicate.start";
  CONST string ADMIN_EVENT_DUPLICATE_END = "austral.admin.event.duplicate.end";

  CONST string ADMIN_EVENT_FORM_START = "austral.admin.event.form.start";
  CONST string ADMIN_EVENT_FORM_HANDLE_REQUEST_BEFORE = "austral.admin.event.form.handle_request.before";
  CONST string ADMIN_EVENT_FORM_HANDLE_REQUEST_AFTER = "austral.admin.event.form.handle_request.after";
  CONST string ADMIN_EVENT_FORM_AUSTRAL_FORM_VALIDATE = "austral.admin.event.form.validate.form";
  CONST string ADMIN_EVENT_FORM_UPDATE_BEFORE = "austral.admin.event.form.update.before";
  CONST string ADMIN_EVENT_FORM_UPDATE_AFTER = "austral.admin.event.form.update.after";
  CONST string ADMIN_EVENT_FORM_FLUSH_AFTER = "austral.admin.event.form.flush.after";
  CONST string ADMIN_EVENT_FORM_END = "austral.admin.event.form.end";

  CONST string ADMIN_EVENT_HTTP_CACHE_START = "austral.admin.event.http_cache.clear.start";
  CONST string ADMIN_EVENT_HTTP_CACHE_END = "austral.admin.event.http_cache.clear.end";

  CONST string ADMIN_EVENT_LIST_START = "austral.admin.event.list.start";
  CONST string ADMIN_EVENT_LIST_HYDRATE = "austral.admin.event.list.hydrate";
  CONST string ADMIN_EVENT_LIST_END = "austral.admin.event.list.end";
  CONST string ADMIN_EVENT_LIST_DASHBOARD = "austral.admin.event.list.dashboard";

  CONST string ADMIN_EVENT_SORTABLE_START = "austral.admin.event.sortable.start";
  CONST string ADMIN_EVENT_SORTABLE_END = "austral.admin.event.sortable.end";

  CONST string ADMIN_EVENT_TRUNCATE_START = "austral.admin.event.truncate.start";
  CONST string ADMIN_EVENT_TRUNCATE_END = "austral.admin.event.truncate.end";


  /**
   * Get request
   * @return Request|null
   */
  public function getRequest(): ?Request;

  /**
   * @param Request $request
   *
   * @return $this
   */
  public function setRequest(Request $request): AdminEventInterface;

  /**
   * Get module
   * @return Module<T>
   */
  public function getCurrentModule(): Module;

  /**
   * Get request
   * @return AdminHandler<T>
   */
  public function getAdminHandler(): AdminHandler;

  /**
   * Get object
   * @return EntityInterface<T>|null
   */
  public function getObject(): ?EntityInterface;

  /**
   * @param EntityInterface<T> $object
   *
   * @return $this
   */
  public function setObject(EntityInterface $object): self;


}