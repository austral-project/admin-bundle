<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Page;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral List Url Store.
 *
 * The last URL of the list of a module (page, filters), kept in the session: the form and the actions go back to
 * it.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ListUrlStore
{
  private const string SESSION_PREFIX = 'austral_admin.list_url.';

  /**
   * @param AdminUrlGenerator $urls
   */
  public function __construct(private readonly AdminUrlGenerator $urls)
  {
  }

  /**
   * @param Module  $module
   * @param Request $request
   *
   * @return void
   */
  public function save(Module $module, Request $request): void
  {
    if ($request->hasSession())
    {
      $request->getSession()->set(self::key($module), $request->getRequestUri());
    }
  }

  /**
   * @param Module  $module
   * @param Request $request
   *
   * @return string The last URL of the list, the list without parameters otherwise
   */
  public function get(Module $module, Request $request): string
  {
    $url = $request->hasSession() ? $request->getSession()->get(self::key($module)) : null;

    return self::isLocal($url) ? $url : $this->urls->module($module);
  }

  /**
   * A path of this site: "//host" and "/\host" are other sites for a browser.
   *
   * @param mixed $url
   *
   * @return bool
   *
   * @phpstan-assert-if-true string $url
   */
  private static function isLocal(mixed $url): bool
  {
    return \is_string($url) && 1 === preg_match('#^/(?![/\\\\])#', $url);
  }

  /**
   * @param Module $module
   *
   * @return string
   */
  private static function key(Module $module): string
  {
    return self::SESSION_PREFIX.implode('/', [$module->getPath(), ...$module->getVariantKeys()]).'@'.($module->getLanguage() ?? '');
  }
}
