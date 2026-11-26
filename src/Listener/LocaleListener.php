<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Listener;

use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\SecurityBundle\Entity\Interfaces\UserInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\LocaleSwitcher;

/**
 * Austral Locale Listener.
 *
 * Language of the interface on the pages of the admin: the language of the user, the preferred language of the
 * browser otherwise, among the languages of the interface. After the firewall (priority 8).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class LocaleListener
{
  /**
   * @param TokenStorageInterface $tokenStorage
   * @param LocaleSwitcher|null   $localeSwitcher
   * @param list<string>          $languages      Languages of the interface, the first one by default
   */
  public function __construct(
    private readonly TokenStorageInterface $tokenStorage,
    private readonly ?LocaleSwitcher $localeSwitcher = null,
    private readonly array $languages = ['en', 'fr'],
  ) {
  }

  /**
   * @param RequestEvent $event
   *
   * @return void
   */
  public function __invoke(RequestEvent $event): void
  {
    $request = $event->getRequest();
    if (!$event->isMainRequest() || true !== $request->attributes->get(AdminRouteLoader::ATTRIBUTE_ADMIN) || !$this->languages)
    {
      return;
    }
    $user = $this->tokenStorage->getToken()?->getUser();
    $language = $user instanceof UserInterface ? $user->getLanguage() : null;
    if (null === $language || !\in_array($language, $this->languages, true))
    {
      $language = $request->getPreferredLanguage($this->languages) ?? $this->languages[0];
    }
    $request->setLocale($language);
    $this->localeSwitcher?->setLocale($language);
  }
}
