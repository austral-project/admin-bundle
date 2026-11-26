<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Twig;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\Security\CsrfGuard;
use Austral\AdminBundle\View\Navigation;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

/**
 * Austral Admin Twig Extension.
 *
 * The global "austral_admin" (configuration of the admin: name, languages) and the functions of the templates.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminExtension extends AbstractExtension implements GlobalsInterface
{
  /**
   * @param Navigation           $navigation
   * @param AdminUrlGenerator    $urls
   * @param CsrfGuard            $csrf
   * @param array<string, mixed> $config     Exposed as the global "austral_admin"
   */
  public function __construct(
    private readonly Navigation $navigation,
    private readonly AdminUrlGenerator $urls,
    private readonly CsrfGuard $csrf,
    private readonly array $config = [],
  ) {
  }

  /**
   * @return array<string, mixed>
   */
  public function getGlobals(): array
  {
    return ['austral_admin' => $this->config];
  }

  /**
   * @return list<TwigFunction>
   */
  public function getFunctions(): array
  {
    return [
      new TwigFunction('austral_admin_navigation', $this->navigation->build(...)),
      new TwigFunction('austral_admin_csrf_token', fn (): string => $this->csrf->token()),
      new TwigFunction('austral_admin_module_url', fn (Module $module, string $action = ModuleAction::LIST, array $parameters = []): string => $this->urls->module($module, $action, $parameters)),
      new TwigFunction('austral_admin_object_url', fn (Module $module, string $action, object $object, array $parameters = []): string => $this->urls->object($module, $action, $object, $parameters)),
      new TwigFunction('austral_admin_language_url', fn (Module $module, string $language, string $action = ModuleAction::LIST, array $parameters = []): string => $this->urls->module($module->withLanguage($language), $action, $parameters)),
    ];
  }
}
