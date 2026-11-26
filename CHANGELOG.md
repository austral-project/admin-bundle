# Changelog

All notable changes to this bundle are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). All Austral bundles share the same major and minor version.

## [Unreleased]

The bundle is rewritten for 4.0 on form-bundle, list-bundle, filter-bundle and security-bundle 4: the same way
of writing an Admin class, without the service container and the routes that change data with a GET. As in 3.1,
every page goes through http-bundle (`AdminHttpEvent`, `HttpAdminEventSubscriber`, `AdminHandler`,
`AdminController`). The styles, scripts and form theme come from design-bundle 4. See UPGRADE-4.0.

### Added

- Configuration `austral_admin` (path, name, languages, list, export, mail, security, modules) checked by a
  configuration tree; `AdminModulesPrepender` for the modules of the other bundles
- `ModuleRegistryPass`: the modules are read and checked once, at the compilation of the container (Admin
  services, entity managers, actions, formats); the cache of the routes follows them
- Route loader `austral_admin`: one route by module and available action, POST + CSRF for the actions that change
  data, optional `/{language}` with `languages.content`
- Attribute `AsAdminModule`: a module of the application declared on its Admin class, the YAML wins
- Variants of the modules (`ModuleVariantProviderInterface`, tag `austral.admin.module_variants`), on several
  levels, replacing the modules cloned by listener and the multi-domain modules; a level with one variant only
  redirects to it (a site with one domain does not show a tile to click)
- `Admin::configureQuery()` (`QueryAdminEvent`): restrictions applied to the list and to every entity read by an
  action (edit, delete, toggle, sort, export, truncate)
- Sections of a list with their own query, pagination and filter group; the last URL of the list is kept
- Toggle of a `SwitchColumn` / `ChoicesColumn` declared in the list (`ListAdminEvent::toggle()`), to one of its
  values only
- Sort (`sortable`), truncate in batches through the entity manager, export CSV / JSON / XLSX with the
  formulas neutralized
- Login link with a confirmation page, password forgotten and reset, mails with symfony/mailer, my account
  with the current password
- `RoleTree`: the roles of the modules in navigation order, for the groups of security-bundle (its bridge
  `Bridge/Admin` brings the modules of the administrators, users, groups and roles)
- Events `AdminEvents::*` for the bridges, `PageRenderEvent`, `DashboardEvent`, `ConfigurationCheckEvent`
- Pipeline of http-bundle on every route of the admin, the pages of the firewall included (attribute
  `_austral_http_event: AdminHttpEvent`): `HttpAdminEventSubscriber` sets the compression
  (`austral_http.compression_gzip.admin`, else `default`) and, after the firewall, the language of the interface,
  the template parameters shared by all the pages (`user`, `language`) and the module of the route (resolved and
  granted before the controller); `AdminHandler` (one method per page) fills the template parameters or sets the
  response; `AdminController` (dashboard, my account, modules) and `SecurityController` render them
- `Template\TemplateParameters` (template, variables and status code of the page) and `View\AdminView` (page
  returned by `ListPage`, `FormPage`, `DashboardPage` and `AccountPage`); the services `austral.admin.handler` and
  `austral.admin.template`, reset between two requests (`kernel.reset`)
- Interface in the language of the user
- Templates on design-bundle 4 (build `austral-design-admin`, form theme `@AustralDesign/Form/austral_layout.html.twig`),
  without inline style nor script; an admin list theme (POST forms with the CSRF token); confirmation of the forms
  that change data (`data-confirm`, component `confirm` of design-bundle)
- Unit and functional tests (test application on SQLite)

### Changed

- Sources in `src/`, PHP 8.4, Symfony 7.4, explicit services
- An Admin is a service tagged `austral.admin` with its dependencies in its constructor; the events carry the
  module and the request
- The modules are configured in `austral_admin.modules` (YAML), no longer in parameters
- The rights are evaluated when they are asked, not for every module on every request; the role names are the
  ones of 3.1
- Download → export (`/export.{format}`), change value → toggle (POST + CSRF), sortable → sort

### Fixed

- The change of a value from the list accepted any field and value (`/change-value/typeUser/root`): only a
  column declared as a switch or choices, with its values, and the rights of the entity
- Delete, duplicate, truncate and change were GET requests without CSRF token
- The configurations of the modules were merged with `array_merge_recursive` (lists doubled)

### Removed

- Mercure notifications (notify-bundle), deployment push, conflict detection, guideline, HTTP cache clear, ping,
  popins of links and graphic items (their bundles bring them back through the events)
- Commands `austral:admin:modules` and `austral:admin:conflict-detect`
- `AdminModuleInterface`, `Modules`, `Module` of 3.1, `DataHydrate`, `austral-service`, the parameters
  `austral.admin.*` and `austral_admin.modules.<bundle>` (`AdminHandler` and `Template\TemplateParameters` are
  rewritten on http-bundle 4)
- Module keys `data_hydrate_class` and `enable_multi_domain`
- The dependencies on entity-file-bundle and notify-bundle (http-bundle, tools-bundle and design-bundle stay)

## [3.1.0] - 2022-09-17

- Moved all the Entities interfaces in bundle  : entity-bundle
- Added manage with multi domain
- Rewrited the Module generator
- Generated admin module auto by domain if entity has filter by domain
- Added new link selection for components and wysiwygs
- Added new icons
- Fix bug in the twig template

## [3.0.0] - 2021-02-10

- Created new repository and new branch in to Github
- Changed the project License to the MIT
- Writing and publish documentation
- Add to Packagist Service for installation with composer

## 2.x and 1.x

These versions were not open source and have no public documentation.

[Unreleased]: https://github.com/austral-project/admin-bundle/compare/v3.1.3...4.x
[3.1.0]: https://github.com/austral-project/admin-bundle/compare/v3.0.0...v3.1.0
[3.0.0]: https://github.com/austral-project/admin-bundle/releases/tag/v3.0.0
