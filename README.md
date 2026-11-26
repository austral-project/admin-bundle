# Austral Admin

[![Version](https://img.shields.io/packagist/v/austral/admin-bundle?label=version)](https://packagist.org/packages/austral/admin-bundle)
[![CI](https://github.com/austral-project/admin-bundle/actions/workflows/ci.yml/badge.svg?branch=4.x)](https://github.com/austral-project/admin-bundle/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/austral/admin-bundle/php?version=4.x-dev)](https://packagist.org/packages/austral/admin-bundle)
[![Symfony](https://img.shields.io/badge/symfony-7.4-black)](https://symfony.com/releases/7.4)
[![License](https://img.shields.io/github/license/austral-project/admin-bundle)](LICENSE)

The Austral back office: modules described in YAML, an `Admin` class by module for its list, filters and form,
the actions on the entities, the rights by role, and the pages of the firewall (login, login link, password
reset, my account). The modules of the users, groups and roles come from the bridge of security-bundle.

> This bundle is part of [Austral](https://github.com/austral-project), a modular back office and CMS for Symfony.

The bundle gives the mechanics and minimal templates, enough to use the admin from a browser. The design (theme,
JavaScript of the sortable lists, confirmations) is in design-bundle, which overrides the templates.

## Requirements

- PHP 8.4 or higher
- Symfony 7.4
- Doctrine ORM 3
- austral/entity-bundle, form-bundle, list-bundle, filter-bundle and security-bundle 4

## Installation

```bash
composer require austral/admin-bundle
```

If you do not use Symfony Flex, register the bundle in `config/bundles.php` (with the Austral bundles it needs):

```php
return [
    // ...
    Austral\AdminBundle\AustralAdminBundle::class => ['all' => true],
];
```

Import the routes of the admin. Their prefix is `austral_admin.path` (`/austral-admin` by default):

```yaml
# config/routes/austral_admin.yaml
austral_admin:
    resource: .
    type: austral_admin
```

The firewall belongs to the application. The firewall of the admin, with the pages of the bundle:

```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_ROOT:                [ ROLE_ADMIN_ACCESS, ROLE_USER_ACCESS ]

    password_hashers:
        App\Entity\Austral\SecurityBundle\User: 'auto'

    providers:
        austral_security_user:
            id:                   austral.security.user_provider

    firewalls:
        dev:
            pattern:              ^/(_(profiler|wdt)|css|images|js)/
            security:             false

        austral_admin:
            pattern:              ^%austral_admin.path%
            lazy:                 true
            provider:             austral_security_user
            user_checker:         austral.security.user_checker
            form_login:
                login_path:           austral_admin_security_login
                check_path:           austral_admin_security_login
                default_target_path:  austral_admin_index
                use_referer:          true
                enable_csrf:          true
            # optional: the login link (austral_security.login_link.firewall: austral_admin)
            login_link:
                check_route:          austral_admin_security_login_link_check
                signature_properties: [ id, email, password, isActive ]
                lifetime:             600
                max_uses:             1
                # the link opens a confirmation page, the login happens on its POST: mail scanners do not use it up
                check_post_only:      true
                login_path:           austral_admin_security_login
                default_target_path:  austral_admin_index
            # needs symfony/rate-limiter
            login_throttling:
                max_attempts:         5
                interval:             '15 minutes'
            remember_me:
                secret:               '%kernel.secret%'
                lifetime:             604800
                signature_properties: [ password, isActive ]
            logout:
                path:                 austral_admin_security_logout
                target:               austral_admin_security_login

    # the first matching rule is used
    access_control:
        - { path: '^%austral_admin.path%/(login|logout|password)', roles: PUBLIC_ACCESS }
        - { path: '^%austral_admin.path%', roles: ROLE_ADMIN_ACCESS }
```

The parameter `austral_admin.path` is set by the bundle before `security.yaml` is read.

## Configuration

```yaml
# config/packages/austral_admin.yaml
austral_admin:
    path:                   /austral-admin   # prefix of the URLs
    name:                   Austral          # name of the project in the admin
    languages:
        interface:          [ en, fr ]       # languages of the interface, the first by default
        content:            [ ]              # languages of the content: the URLs of the forms end with /{language}
    list:
        per_page:           30               # 0 for no pagination
    export:
        csv_delimiter:      ','
    mail:                                    # sender of the security mails (symfony/mailer), no mail when null
        from:               ~
        from_name:          ~
    security:
        password_reset:     false            # pages "password forgotten" and "reset"
    modules:                                 # see below
```

The interface is in the language of the user (`UserInterface::getLanguage()`), or the preferred language of the
browser among `languages.interface`.

## Modules

A module is described in YAML, by key. A bundle adds its modules with `AdminModulesPrepender` (see Bridges); the
application adds its own and changes or disables the others:

```yaml
austral_admin:
    modules:
        pages:
            name:               Pages
            picto:              austral-picto-file
            class:              App\Admin\PageAdmin              # Admin service (or class)
            entity_manager:     app.entity_manager.page          # service id of an Austral entity manager
            sortable:           true                             # field "position" (or the name of the field)
            truncate:           true                             # action "delete all"
            downloadFormats:    [ csv, json, xlsx ]              # xlsx needs phpoffice/phpspreadsheet
            extendActions:
                publish:        publish                          # action on an entity => method of the Admin
            disabledActions:    [ duplicate ]
            navigation:
                position:       10
        security:
            children:
                users:          { enabled: false }               # a module of the bundle, disabled
```

| Key | Default | |
|---|---|---|
| `enabled` | `true` | `false` removes the module and its children |
| `name` | the key | Name of the module (roles, logs); the translations are `pages.names.<key>.singular` / `.plural`, the key being the path in camel case (`securityAdminUsers`) |
| `picto`, `pictoTile` | `null` | CSS classes of the navigation and of the tiles |
| `route` | the key | Segment of the URL; the path of a module is the routes of its parents and its route (`security/admin-users`) |
| `class` | `null` | Admin class (service id or class) |
| `entity_manager` | `null` | Service id of an entity manager (`EntityManagerORMInterface`) |
| `entity` | from the entity manager | Entity class, when the entity manager does not give it (`$class` argument) |
| `navigation` | `{ enabled: true, position: 0 }` | |
| `translate` | `null` | Gender of the translations (`male_v`, `female_c`...), parameter `%module_gender%` |
| `sortable` | `false` | `true` (field `position`) or the position field |
| `truncate` | `false` | |
| `actions` | `[]` | Page module: action => method of the Admin, `index` for the page of the module |
| `extendActions` | `[]` | Entity module: action on an entity => method of the Admin |
| `disabledActions` | `[]` | `create`, `edit`, `duplicate`, `delete`, `truncate`, `toggle` (`change`), `sort`, `export` (`download`), an extra action |
| `downloadFormats` | `[]` | `csv`, `json`, `xlsx`: no export without format |
| `security_key` | the path | Base of the roles |
| `children` | `[]` | Modules under this one (4 levels) |

Three kinds of modules:

- **entity module**: `entity_manager` and `class`, a list, a form and the actions on the entities;
- **page module**: `class` without `entity_manager`, its `actions` are methods of the Admin;
- **container module**: neither, it shows the tiles of its children.

The configuration is read once, when the container is compiled: an unknown entity manager, a missing Admin, an
unknown action or format fails there.

### Modules declared by attribute

A module of the application can be declared on its Admin class instead of YAML:

```php
use Austral\AdminBundle\Attribute\AsAdminModule;

#[AsAdminModule(parent: 'catalog', entityManager: 'app.entity_manager.category', sortable: true, downloadFormats: ['csv'])]
final class CategoryAdmin extends Admin
{
}
```

- The arguments are the keys of the YAML (`entityManager` for `entity_manager`, `securityKey` for
  `security_key`), checked the same way; `class` is the service of the Admin.
- `key` defaults to the class name without `Admin`, in kebab case (`ProductCategoryAdmin`: `product-category`).
- `parent` gives the keys of the parent module joined by `/` (`catalog`, `security`, `catalog/products`): it must
  be a module of the YAML or of another attribute. Without `parent`, the module is at the root.
- The YAML wins: the modules of the bundles and the configuration of the application change or disable a module
  of an attribute (`catalog: { children: { category: { enabled: false } } }`).
- The attribute can be repeated: one Admin for several modules.
- The class must be a service tagged `austral.admin` (automatic with autoconfigure). The bundles declare their
  modules in YAML (see Bridges), so that the application sees and changes them.

## Admin classes

```php
namespace App\Admin;

use App\Entity\Page;
use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Event\FilterAdminEvent;
use Austral\AdminBundle\Event\FormAdminEvent;
use Austral\AdminBundle\Event\ListAdminEvent;
use Austral\AdminBundle\Event\PageAdminEvent;
use Austral\AdminBundle\Event\QueryAdminEvent;
use Austral\FilterBundle\Filter\SearchFilter;
use Austral\FormBundle\Field;
use Austral\ListBundle\Column;

final class PageAdmin extends Admin
{
    public function __construct(private readonly PagePublisher $publisher)
    {
    }

    public function getEvents(): array
    {
        return [AdminEvents::FORM_SAVE_BEFORE => 'beforeSave'];
    }

    public function configureQuery(QueryAdminEvent $event): void
    {
        // list, edit, delete, toggle, export, truncate: an entity outside the restrictions is a 404
        $event->getQueryBuilder()->andWhere($event->getRootAlias().'.isArchived = false');
    }

    public function configureListMapper(ListAdminEvent $event): void
    {
        $event->getMapper()
            ->add(new Column\ValueColumn('name'))
            ->add(new Column\SwitchColumn('isPublished', $event->toggle('isPublished')))
            ->add(new Column\ChoicesColumn('status', ['choices' => ['draft' => 'Draft', 'online' => 'Online']] + $event->toggle('status')))
            ->add(new Column\DateColumn('updated'));
        $event->addSection('homepage', 'sections.homepage', static fn ($qb, string $alias) => $qb->andWhere("{$alias}.isHomepage = true"))
            ->addSection('default', false, static fn ($qb, string $alias) => $qb->andWhere("{$alias}.isHomepage = false"));
    }

    public function configureFilterMapper(FilterAdminEvent $event): void
    {
        $event->getMapper()->filter()->add(new SearchFilter('search', ['properties' => ['name']]));
    }

    public function configureFormMapper(FormAdminEvent $event): void
    {
        $event->getMapper()->addFieldset('general')
            ->add(Field\TextField::create('name', ['required' => true]))
            ->add(Field\SwitchField::create('isPublished'));
    }

    protected function beforeSave(FormAdminEvent $event): void
    {
    }

    public function publish(PageAdminEvent $event): void
    {
        if ($event->getRequest()->isMethod('POST')) { // CSRF checked by the admin
            $this->publisher->publish($event->getObject());
        }
    }
}
```

An Admin is a service tagged `austral.admin` (automatic for the classes of the application that extend `Admin`):
it receives its dependencies in its constructor and keeps no state of a request, everything comes with the
events. A class that is not a service is registered without arguments.

The `configure*` methods are called for the events of `Admin::DEFAULT_EVENTS`. `getEvents()` adds methods to the
other events: `event => method`, `[method, priority]` or a list of them; protected methods are allowed.

- **List** (`ListAdminEvent`): the list mapper of list-bundle, the default actions already added (create,
  export, delete all, edit, extra actions, duplicate, delete; remove them with `removeAction()` /
  `removeRowAction()`), the sections (`addSection()`, one query, pagination and filter group each) and the number
  by page (`setPerPage()`). Without column, the entities are shown as strings.
- **Toggle**: `$event->toggle('field')` gives the `url` and `granted` options of a `SwitchColumn` or a
  `ChoicesColumn`. Only these columns can be changed from the list, with their values only, in a POST with the
  CSRF token; without the right the column is displayed only.
- **Filters** (`FilterAdminEvent`): the filter mapper of filter-bundle; the group of a section's name filters the
  section, the group `default` the other ones.
- **Form** (`FormAdminEvent`): the form mapper of form-bundle, the entity and `isCreate()`. The form is handled
  by the FormHandler of form-bundle and saved by the entity manager.
- **Query** (`QueryAdminEvent`): root alias `root`, `getContext()` tells which query (`list`, `object`, `export`,
  `truncate`, `sort`).

## Variants

A variant is a filtered version of an entity module: the products of a category, the pages of a domain. A
provider gives the variants of the modules it supports:

```php
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleDefinition;
use Austral\AdminBundle\Variant\ModuleVariant;
use Austral\AdminBundle\Variant\ModuleVariantProviderInterface;

final class CategoryVariantProvider implements ModuleVariantProviderInterface
{
    public function __construct(private readonly CategoryRepository $categories)
    {
    }

    public static function supports(ModuleDefinition $definition): bool
    {
        return Product::class === $definition->getEntityClass();   // called once, at the compilation
    }

    public function variants(Module $module, array $parents): iterable
    {
        foreach ($this->categories->findAll() as $category) {
            yield new ModuleVariant(
                $category->getSlug(),
                $category->getName(),
                query: static fn ($qb, string $alias) => $qb->andWhere("{$alias}.category = :category")->setParameter('category', $category),
                create: static fn (Product $product) => $product->setCategory($category),
            );
        }
    }
}
```

The provider is a service tagged `austral.admin.module_variants` (automatic with autoconfigure); the priority of
the tag orders the levels, the highest first. The URLs get a segment by level: `/products` shows the tiles of
the categories, `/products/{category}` the list, `/products/{category}/{id}/edit` the form. An unknown key is a
404; the rights stay those of the module, the URLs of a page keep its variants.

## Rights

The roles keep the names of 3.1: `ROLE_` and the security key (the path by default) in upper snake case, then
the suffix of the action.

| Action | Role (module `security/users`) |
|---|---|
| list, page of a page module | `ROLE_SECURITY_USERS` |
| create, edit, duplicate, delete, truncate | `ROLE_SECURITY_USERS_CREATE`, `_EDIT`, `_DUPLICATE`, `_DELETE`, `_TRUNCATE` |
| toggle (change a value from the list) | `ROLE_SECURITY_USERS_CHANGE` |
| sort | `ROLE_SECURITY_USERS_EDIT` |
| export | `ROLE_SECURITY_USERS_DOWNLOAD` |
| extra action `publish`, action `refresh` of a page module | `ROLE_SECURITY_USERS_PUBLISH`, `..._REFRESH` |

An action also needs the role of its module. A container module is open to the users of one of its children. The
dashboard needs `ROLE_ADMIN_ACCESS`; a root user has every right (`RootVoter` of security-bundle). The rights are
evaluated when they are asked, not for every module on every request.

`php bin/console austral:admin:roles` creates the roles of the modules missing in the database (`--dry-run` to
list them); the form of the groups also creates a role when it is given.

## URLs

| Route | Path | Method |
|---|---|---|
| `austral_admin_index` | `/` | GET |
| `austral_admin_security_login` | `/login` | GET, POST (form_login) |
| `austral_admin_security_logout` | `/logout` | firewall |
| `austral_admin_security_login_link` | `/login/link` | GET, POST |
| `austral_admin_security_login_link_check` | `/login/link/check` | GET (confirmation), POST (login) |
| `austral_admin_security_password_forgot` | `/password/forgot` | GET, POST |
| `austral_admin_security_password_reset` | `/password/reset/{token}` | GET, POST |
| `austral_admin_my_account` | `/me` | GET, POST |
| `austral_admin.module.<path>.list` | `/pages` | GET |
| `….create` | `/pages/new` | GET, POST |
| `….edit` | `/pages/{id}/edit` | GET, POST |
| `….duplicate`, `….delete` | `/pages/{id}/duplicate`, `/pages/{id}/delete` | POST + CSRF |
| `….truncate` | `/pages/delete-all` | POST + CSRF |
| `….toggle` | `/pages/{id}/toggle/{field}?value=…` | POST + CSRF |
| `….sort` | `/pages/sort` (`ids[]` in the new order) | POST + CSRF |
| `….export` | `/pages/export.{format}` | GET |
| `….<extra action>` | `/pages/{id}/publish` | GET, POST (+ CSRF) |
| `….variants<level>` | `/products`, `/products/{variant0}` | GET |

In a route name the `/` of the path is a `.` (`austral_admin.module.security.users.edit`). With
`languages.content`, the list and the forms end with an optional `/{language}`. Only the available actions have
a route. The CSRF token has the id `austral_admin`, in the field `_token` or the header `X-CSRF-Token`; the
layout gives it in `<meta name="austral-admin-csrf-token">` and the Twig function `austral_admin_csrf_token()`.
An asynchronous request (`X-Requested-With` or `Accept: application/json`) gets a JSON answer.

`AdminUrlGenerator` (`austral.admin.url_generator`) builds the URLs of a module with its variants and its
language.

## Security screens

- **Login** with `form_login` (CSRF id `authenticate`), links to the login link and the password reset when they
  are enabled.
- **Login link**: the request page answers the same way whether the account exists or not; the link opens a
  confirmation page whose POST logs in (`check_post_only`).
- **Password forgotten / reset** (`security.password_reset: true`): `PasswordResetManager` of security-bundle,
  the new password at least 8 characters with a lowercase, an uppercase and a digit.
- **Mails**: with symfony/mailer and `mail.from`, the links are sent by mail (templates `email/*.html.twig`).
  Without, listen to `SecurityEvents::PASSWORD_RESET_REQUESTED` and `LOGIN_LINK_REQUESTED`.
- **My account** (`/me`): name, email, username, language, theme and password, the current password required.
- **Modules** `security/admin-users`, `security/users`, `security/groups`, `security/roles`: in the bridge of
  security-bundle (`Austral\SecurityBundle\Bridge\Admin`), loaded with admin-bundle. The form of the groups uses
  `RoleTree` of this bundle (the roles of the modules, in navigation order).

## Dashboard and configuration

The dashboard shows the blocks of `AdminEvents::DASHBOARD` (`DashboardEvent::addBlock(name, template, vars,
position)`), the tiles of the modules first. The page module `status-configuration` shows the checks of
`AdminEvents::CONFIGURATION_CHECK` (`ConfigurationCheck`: HTTPS, environment, debug, mails, login link, and the
checks of the other bundles).

## Events

| Event | Object | |
|---|---|---|
| `austral.admin.query` | `QueryAdminEvent` | every query of an entity module |
| `austral.admin.list.configure` | `ListAdminEvent` | |
| `austral.admin.filter.configure` | `FilterAdminEvent` | |
| `austral.admin.form.configure` | `FormAdminEvent` | |
| `austral.admin.form.save_before`, `.save_after` | `FormAdminEvent` | with the form |
| `austral.admin.export.configure` | `ExportAdminEvent` | columns and file name |
| `austral.admin.object.delete_before`, `.delete_after` | `ObjectAdminEvent` | also for each entity of the truncate |
| `austral.admin.object.duplicate` | `ObjectAdminEvent` | the copy, before it is saved, with its source |
| `austral.admin.object.toggle` | `ObjectAdminEvent` | field and value, before the save |
| `austral.admin.object.sort` | `SortAdminEvent` | |
| `austral.admin.page.render` | `PageRenderEvent` | template and variables of every page |
| `austral.admin.dashboard` | `DashboardEvent` | |
| `austral.admin.configuration_check` | `ConfigurationCheckEvent` | |

The module events go to the methods of the Admin of the module first (`getEvents()`), then to the listeners of
the application: a bridge listens to them for every module (`$event->getModule()->getEntityClass()`).

## Bridges

A bundle adds its modules from a YAML file in the `prepend()` of its extension; nothing happens when admin-bundle
is not enabled:

```php
use Austral\AdminBundle\DependencyInjection\AdminModulesPrepender;

public function prepend(ContainerBuilder $container): void
{
    AdminModulesPrepender::prepend($container, __DIR__.'/../Bridge/Admin/austral_admin.yaml');
}
```

## Templates

`@AustralAdmin/layout.html.twig` and the pages (`module/list`, `module/form`, `module/tiles`, `module/page`,
`dashboard`, `configuration`, `security/*`, `email/*`) are minimal. The list theme
`@AustralAdmin/list/austral_admin_list.html.twig` renders the actions with `data-method="post"`, the switches
and the choices as forms with the CSRF token. Override them in `templates/bundles/AustralAdminBundle/` or with
design-bundle. Globals: `austral_admin` (name, path, languages); functions: `austral_admin_navigation()`,
`austral_admin_csrf_token()`, `austral_admin_module_url()`, `austral_admin_object_url()`,
`austral_admin_language_url()`. The translations are in the domain `austral`.

## Services

| Id | Class |
|----|-------|
| `austral.admin.module_registry` | `ModuleRegistry` (the definitions) |
| `austral.admin.module_factory` | `ModuleFactory` (the module of a request, its variants) |
| `austral.admin.url_generator` | `AdminUrlGenerator` |
| `austral.admin.event_dispatcher` | `AdminEventDispatcher` |
| `austral.admin.renderer` | `AdminRenderer` |

## Documentation

The full documentation is available at https://austral.dev.

## Versions

| Bundle | Symfony | PHP | Branch |
|--------|---------|-----|---------|
| 4.x    | 7.4     | ≥ 8.4 | `4.x` |
| 3.1    | 5.4 / 6.4 | ≥ 8.0 | `3.1` (maintenance) |

See the [CHANGELOG](CHANGELOG.md) and, to upgrade from 3.1, [UPGRADE-4.0](UPGRADE-4.0.md).

## Contributing

Contributions are welcome: see the [contributing guide](https://github.com/austral-project/.github/blob/master/CONTRIBUTING.md). To report a security issue, follow the [security policy](https://github.com/austral-project/.github/blob/master/SECURITY.md).

## License

MIT, see [LICENSE](LICENSE).

## Credits

Created by [Matthieu Beurel](https://www.mbeurel.com). Sponsored by [Yipikai Studio](https://yipikai.studio).
