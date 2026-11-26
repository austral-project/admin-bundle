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

/**
 * Austral Admin Events.
 *
 * The module events are given to the methods of the Admin of the module (Admin::getEvents()), then dispatched
 * with the event dispatcher: a bridge of another bundle listens to them for every module.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminEvents
{
  /**
   * A query of an entity module (list, object, export, truncate, sort, toggle): restrictions go here, they apply
   * to the list and to the loading of a single entity. QueryAdminEvent.
   */
  public const string QUERY = 'austral.admin.query';

  /**
   * Columns, actions and sections of the list. ListAdminEvent.
   */
  public const string LIST_CONFIGURE = 'austral.admin.list.configure';

  /**
   * Filters of the list. FilterAdminEvent.
   */
  public const string FILTER_CONFIGURE = 'austral.admin.filter.configure';

  /**
   * Fields of the form. FormAdminEvent.
   */
  public const string FORM_CONFIGURE = 'austral.admin.form.configure';

  /**
   * The form is valid, before the entity is saved. FormAdminEvent.
   */
  public const string FORM_SAVE_BEFORE = 'austral.admin.form.save_before';

  /**
   * After the entity of the form is saved. FormAdminEvent.
   */
  public const string FORM_SAVE_AFTER = 'austral.admin.form.save_after';

  /**
   * Columns of the export, a copy of the list mapper. ExportAdminEvent.
   */
  public const string EXPORT_CONFIGURE = 'austral.admin.export.configure';

  /**
   * Before an entity is deleted. ObjectAdminEvent.
   */
  public const string DELETE_BEFORE = 'austral.admin.object.delete_before';

  /**
   * After an entity is deleted. ObjectAdminEvent.
   */
  public const string DELETE_AFTER = 'austral.admin.object.delete_after';

  /**
   * The copy of an entity, before it is saved. ObjectAdminEvent (source: the entity copied).
   */
  public const string DUPLICATE = 'austral.admin.object.duplicate';

  /**
   * The value of a switch or choices column is changed, before it is saved. ObjectAdminEvent.
   */
  public const string TOGGLE = 'austral.admin.object.toggle';

  /**
   * The positions are changed, before they are saved. SortAdminEvent.
   */
  public const string SORT = 'austral.admin.object.sort';

  /**
   * Before a page of the admin is rendered (template and variables can change). PageRenderEvent.
   */
  public const string PAGE_RENDER = 'austral.admin.page.render';

  /**
   * Blocks of the dashboard. DashboardEvent.
   */
  public const string DASHBOARD = 'austral.admin.dashboard';

  /**
   * Checks of the configuration page. ConfigurationCheckEvent.
   */
  public const string CONFIGURATION_CHECK = 'austral.admin.configuration_check';
}
