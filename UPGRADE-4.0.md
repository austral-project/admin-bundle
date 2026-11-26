# Upgrade from 3.1 to 4.0

## Requirements

- PHP 8.4 minimum
- Symfony 7.4
- Doctrine ORM 3

## Update the dependencies

```diff
- "austral/admin-bundle": "^3.1"
+ "austral/admin-bundle": "^4.0"
```

All `austral/*` packages must be upgraded to `^4.0` at the same time. admin-bundle depends on http-bundle and
tools-bundle, no longer on design-bundle, entity-file-bundle and notify-bundle: require the ones the application
uses. Enable `Austral\HttpBundle\AustralHttpBundle` before `AustralAdminBundle` in `config/bundles.php` (Flex
does it). For the XLSX export, require `phpoffice/phpspreadsheet`; for the security mails, `symfony/mailer`.

## Breaking changes

### Sources, routes and configuration

The sources are in `src/` (namespace unchanged). The routes are imported with the loader of the bundle, the file
`Resources/config/routes/admin.yaml` is removed:

```diff
  # config/routes/austral_admin.yaml
  austral_admin:
-     resource: '@AustralAdminBundle/Resources/config/routes/admin.yaml'
-     prefix: /%austral.admin.path%
+     resource: .
+     type: austral_admin
```

The parameters `austral.admin.*` become the configuration `austral_admin`:

| 3.1 | 4.0 |
|---|---|
| `austral.admin.path: austral-admin` | `austral_admin.path: /austral-admin` (parameter `%austral_admin.path%`, with its `/`) |
| `austral.admin.project.name` | `austral_admin.name` |
| `austral.admin.project.forgot_enabled` | `austral_admin.security.password_reset` |
| `austral.admin.language.user` | `austral_admin.languages.interface` |
| `austral.admin.language.list` / `enabled_multi` | `austral_admin.languages.content` |
| `austral.admin.download.*` | `austral_admin.export.csv_delimiter` (the theme of the XLSX is removed) |
| `austral.admin.modules.actions.entities`, `data_reload_js`, `compression_gzip`, `default.class`, `data_hydrate.class` | removed |
| `austral_admin.modules.<bundle>` (parameter) | `austral_admin.modules` (configuration) |

### security.yaml

The firewall keeps its route names (`austral_admin_security_login`, `austral_admin_security_logout`,
`austral_admin_index`). Change its pattern and the access control, the path now starts with `/`:

```diff
  firewalls:
      austral_admin:
-         pattern: ^/%austral.admin.path%
+         pattern: ^%austral_admin.path%
  access_control:
-     - { path: ^/%austral.admin.path%/(login|logout|password), roles: PUBLIC_ACCESS }
-     - { path: ^/%austral.admin.path%, roles: ROLE_ADMIN_ACCESS }
+     - { path: '^%austral_admin.path%/(login|logout|password)', roles: PUBLIC_ACCESS }
+     - { path: '^%austral_admin.path%', roles: ROLE_ADMIN_ACCESS }
```

The route `/translation-{lang}.js` is removed (remove its access control rule). The README gives the whole
firewall, with the login link (`austral_admin_security_login_link_check`, `check_post_only: true`).

### Modules

The modules move from the parameter `austral_admin.modules.<bundle>` to the configuration:

```diff
- parameters:
-     austral_admin.modules.app:
-         pages:
-             entity_manager: "austral.entity_manager.page"
+ austral_admin:
+     modules:
+         pages:
+             entity_manager: app.entity_manager.page
              name: Pages
              route: pages
              class: App\Admin\PageAdmin
```

- The other keys are kept: `enabled`, `name`, `picto`, `pictoTile`, `route`, `class`, `entity_manager`,
  `navigation`, `translate`, `sortable`, `truncate`, `actions`, `extendActions`, `disabledActions`,
  `downloadFormats`, `security_key`, `children`.
- `data_hydrate_class` and `enable_multi_domain` are removed (an unknown key fails); the modules by domain or by
  category become variants (see below).
- There is no naming convention of the entity manager any more: an entity module gives its `entity_manager`, and
  an entity module needs a `class`.
- `actions.index` of an entity module is ignored; in a container module (`listChildrenModules`) it is not needed.
- The lists are replaced, not merged: `downloadFormats: [csv]` in the application replaces the formats of a
  bundle. Without `downloadFormats` a module has no export.
- A bundle adds its modules with `AdminModulesPrepender::prepend()` in the `prepend()` of its extension, from
  `Bridge/Admin/austral_admin.yaml`.

The paths and the roles do not change: the groups of the database keep their rights.

### Admin classes

An Admin is now a service with its dependencies in its constructor; the container, the translator, the module
and the entity are no longer set on it. The events carry the module (`getModule()`), the request (`getRequest()`)
and the mappers.

| 3.1 | 4.0 |
|---|---|
| `extends Admin implements AdminModuleInterface` | `extends Admin` |
| `configureListMapper(ListAdminEvent)`: `getListMapper()->getSection('default')->addColumn(new Column\Value('name'))` | `getMapper()->add(new ValueColumn('name'))` (list-bundle 4) |
| `buildDataHydrate(...)->addQueryBuilderClosure(...)` | `configureQuery(QueryAdminEvent)` (also applied to every entity of an action) |
| `addQueryBuilderPaginatorClosure` (order) | `configureQuery()` with `$event->is(QueryAdminEvent::CONTEXT_LIST)` |
| `setMaxResult(100)` | `$event->setPerPage(100)` |
| `getSection('homepage')` | `$event->addSection('homepage', $label, $query)` |
| `Column\SwitchValue('isActive', ..., generateUrl('change'), isGranted('edit'))` | `new SwitchColumn('isActive', $event->toggle('isActive'))` |
| `configureFilterMapper(FilterEventInterface)`, `FilterType\StringType` | `configureFilterMapper(FilterAdminEvent)`, filters of filter-bundle 4 |
| `configureFormMapper(FormAdminEvent)`: `getFormMapper()` | the same, form-bundle 4 (`getMapper()` is an alias) |
| `configurationDownload(DownloadAdminEvent)` | `AdminEvents::EXPORT_CONFIGURE` (`ExportAdminEvent`), the list columns by default |
| `$this->container->get(...)` | constructor injection |
| `$formAdminEvent->getAdminHandler()->getUser()` | inject `TokenStorageInterface` or `Security` |

`getEvents()` keeps its writing (`event => method`, `[method, priority]`, a list), with the new event names:

| 3.1 | 4.0 |
|---|---|
| `ADMIN_EVENT_LIST_START`, `LIST_HYDRATE`, `LIST_END` | `AdminEvents::LIST_CONFIGURE`, `QUERY` |
| `ADMIN_EVENT_FORM_START` | `AdminEvents::FORM_CONFIGURE` |
| `ADMIN_EVENT_FORM_UPDATE_BEFORE`, `FORM_HANDLE_REQUEST_*`, `FORM_AUSTRAL_FORM_VALIDATE` | `AdminEvents::FORM_SAVE_BEFORE` (form-bundle events for the rest) |
| `ADMIN_EVENT_FORM_UPDATE_AFTER`, `FORM_FLUSH_AFTER`, `FORM_END` | `AdminEvents::FORM_SAVE_AFTER` |
| `ADMIN_EVENT_DELETE_START`, `DELETE_END` | `AdminEvents::DELETE_BEFORE`, `DELETE_AFTER` |
| `ADMIN_EVENT_DUPLICATE_START`, `DUPLICATE_END` | `AdminEvents::DUPLICATE` |
| `ADMIN_EVENT_CHANGE_VALUE_START`, `CHANGE_VALUE_END` | `AdminEvents::TOGGLE` |
| `ADMIN_EVENT_SORTABLE_START`, `SORTABLE_END` | `AdminEvents::SORT` |
| `ADMIN_EVENT_TRUNCATE_START`, `TRUNCATE_END` | `AdminEvents::DELETE_BEFORE` / `DELETE_AFTER` for each entity |
| `ADMIN_EVENT_DOWNLOAD_START`, `DOWNLOAD_END` | `AdminEvents::EXPORT_CONFIGURE` |
| `ADMIN_EVENT_ACTION_START`, `ACTION_END` | the method of the action receives a `PageAdminEvent` |
| `ADMIN_EVENT_HTTP_CACHE_*`, `LIST_DASHBOARD` | removed |

An extra action (`extendActions`) or an action of a page module is a method receiving a `PageAdminEvent`: it sets
a response, or a template and its variables. A POST needs the CSRF token of the admin.

### Global events

| 3.1 | 4.0 |
|---|---|
| `austral.event.admin.dashboard` (`DashboardEvent`) | `austral.admin.dashboard` (`DashboardEvent::addBlock()`) |
| `austral.event.admin.configuration_checker` | `austral.admin.configuration_check` (`ConfigurationCheckEvent::add(ConfigurationCheck)`) |
| `AdminHttpEvent` on `austral.event.http.*` | unchanged: every route of the admin has `_austral_http_event: AdminHttpEvent`; check `$httpEvent instanceof AdminHttpEvent` in a subscriber, the events are shared with website-bundle |
| | `austral.admin.page.render` (`PageRenderEvent`): template and variables of a page |
| `austral.event.module.admin.add` / `remove` (`ModuleEvent`) | variants (`ModuleVariantProviderInterface`) |

### HTTP pipeline: handler, subscriber and template parameters

As in 3.1, the admin goes through http-bundle. Their roles change:

| 3.1 | 4.0 |
|---|---|
| `HttpAdminEventSubscriber::onRequestInitialise()`: language of the interface from the session | `onRequestInitialise()`: compression (`compression_gzip.admin`, else `default`); the language is set in `onRequest()`, after the firewall, from the user (`UserInterface::getLanguage()`), else the browser |
| `HttpAdminEventSubscriber::onRequest()`: `Modules`, rights, handler, template parameters (`user`, `variables`, `graphicItems`, `language`, `mercure`, `nameByKeysLinks`) | `onRequest()`: `ModuleResolver` (404 / 403 before the controller), `AdminHandler`, template parameters `user`, `language` (`interface`, `current`, `default`, `list`) and `module` |
| `AdminHandler` (`index()`, `form()`, `action()`…, `getRedirectUrl()`) | `AdminHandler`: one method per page, delegated to `Page\*`; `view(AdminView)` fills the template parameters, `getResponse()` is the redirection, the JSON or the export |
| `AdminController` (`index`, `form`, `delete`…) | `AdminController` (`index`, `account`, `list`, `variants`, `container`, `create`, `edit`, `duplicate`, `delete`, `truncate`, `toggle`, `sort`, `export`, `action`, `page`) and `SecurityController`; both extend `AbstractAdminController` |
| `Template\TemplateParameters` (`austral.admin.template`) | `Template\TemplateParameters` (`austral.admin.template`) with the status code of the response |

`variables`, `graphicItems` and `nameByKeysLinks` are no longer read from the container: the bundle that has them
adds them from its bridge, after `HttpAdminEventSubscriber::PRIORITY`:

```php
public static function getSubscribedEvents(): array
{
    return [HttpEventInterface::EVENT_AUSTRAL_HTTP_REQUEST => ['onRequest', HttpAdminEventSubscriber::PRIORITY - 1]];
}

public function onRequest(HttpEventInterface $httpEvent): void
{
    if ($httpEvent instanceof AdminHttpEvent)
    {
        $httpEvent->getHandler()?->getTemplateParameters()?->addParameters('graphicItems', $this->pictos->all());
    }
}
```

The handler and the template parameters are shared services reset between two requests (`kernel.reset`): never
keep them in a property of another service for the next request. A redirection of the admin is a 302 response,
not `HttpHandler::setRedirectUrl()` (301 by default, cached by the browsers).

### Modules generated by a listener → variants

The listeners that cloned a module by category or by domain become a variant provider:

```php
final class CategoryVariantProvider implements ModuleVariantProviderInterface
{
    public static function supports(ModuleDefinition $definition): bool
    {
        return Product::class === $definition->getEntityClass();
    }

    public function variants(Module $module, array $parents): iterable
    {
        // one ModuleVariant by category: key (URL), name, query restriction, preparation of a new entity
    }
}
```

The providers of several levels are ordered by the priority of their tag. The URLs become
`/products/{category}/...`; the rights stay those of the module.

### URLs and actions

| 3.1 | 4.0 |
|---|---|
| `/{modulePath}` | `/pages` (route `austral_admin.module.pages.list`) |
| `/{modulePath}/{id}/form` | `/pages/{id}/edit` |
| `/{modulePath}/create/form` | `/pages/new` |
| `/{modulePath}/{id}/delete` (GET) | `/pages/{id}/delete` (POST + CSRF) |
| `/{modulePath}/{id}/duplicate` (GET), `/duplicate/domain-{domainId}` | `/pages/{id}/duplicate` (POST + CSRF), the domains are variants |
| `/{modulePath}/truncate` (GET) | `/pages/delete-all` (POST + CSRF) |
| `/{modulePath}/{id}/change-value/{fieldname}/{value}` (GET) | `/pages/{id}/toggle/{field}?value=` (POST + CSRF), declared columns only |
| `/{modulePath}/sortable` | `/pages/sort` (POST + CSRF, `ids[]`) |
| `/{modulePath}/download/{format}` | `/pages/export.{format}` |
| `/{modulePath}/{id}/action/{actionKey}` | `/pages/{id}/{action}` |
| `/{modulePath}/filter-delete/...` | the links of the active filters (filter-bundle) |
| `/dashboard/{language}` | `/` (the language of the user) |
| `.../{language}` | `.../{language}`, only with `languages.content` |
| `/me`, `/me/change-value/{fieldname}/{value}` | `/me` (`austral_admin_my_account`), a form with the current password |
| `/translation-{_locale}.js`, `/popin-*`, `/deployment/push`, `/ping`, `/_guideline` | removed |

The route names of the modules are generated (`austral_admin.module.<path>.<action>`): use
`AdminUrlGenerator` instead of `Module::generateUrl()`.

### Security

- The login page and the account page come from security-bundle 3.1 and are now in this bundle.
- The modules of the users, administrators, groups and roles are in the bridge of security-bundle 4
  (`Austral\SecurityBundle\Bridge\Admin`), added when both bundles are enabled; see its UPGRADE-4.0.
- `Listener\Security\AuthenticationListener` is removed: the login is handled by the firewall.
- The command `austral:admin:roles` creates the missing roles of the modules; the form of the groups creates a
  role when it is given.

### Removed

| 3.1 | 4.0 |
|---|---|
| `Module\Modules`, `Module\Module`, `Admin\AdminModuleInterface`, `Admin\DefaultAdmin` | `ModuleRegistry`, `ModuleFactory`, `Module`, `Page\*` |
| `Admin\ConfigurationStatusAdmin`, `Configuration\ConfigurationChecker*` | `Admin\ConfigurationAdmin`, `ConfigurationCheckEvent` |
| `Template\Elements\Navigation`, `Template\Elements\Breadcrumb` | `View\Navigation`, `View\Breadcrumb` |
| `Dashboard\*` (Tile, OnOff, Action values) | `DashboardEvent::addBlock()` with a template |
| `Services\Download` | `Export\Exporter` |
| `Services\Deployment`, `ConflictDetect`, `Guideline`, Mercure, HTTP cache clear | removed |
| `Command\AdminModulesCommand`, `AdminConflictDetectCommand` | removed |
| `Listener\FormListener` (select of the language) | the language of the content is in the URL |
| `EventSubscriber\ModuleEventSubscriber`, `DashboardSubscriber`, `ConfigurationCheckerSubscriber` | variants, `DashboardEvent`, `ConfigurationCheckEvent` (`HttpAdminEventSubscriber` is kept, see above) |
| `Skeleton`, `austral-service` | removed |

### Templates and translations

The templates are rewritten (minimal markup, the design is in design-bundle): an override of a 3.1 template
must be written again. The translations stay in the domain `austral`; the keys of the pages (`pages.names.*`) are
kept, the keys of the screens are new (see `Resources/translations`).
