<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Listener\Security;

use Austral\SecurityBundle\Entity\Interfaces\UserInterface;
use Austral\SecurityBundle\Security\Authorization\AuthorizationChecker;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

/**
 * Austral Authentication Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AuthenticationListener
{

  /**
   * @var Request|null
   */
  protected ?Request $request;

  /**
   * @var AuthorizationChecker
   */
  protected AuthorizationChecker $authorizationChecker;

  /**
   * @param RequestStack $requestStack
   * @param AuthorizationChecker $authorizationChecker
   */
  public function __construct(RequestStack $requestStack, AuthorizationChecker $authorizationChecker)
  {
    $this->request = $requestStack->getCurrentRequest();
    $this->authorizationChecker = $authorizationChecker;
  }

  /**
   * loginSuccessEvent
   *
   * @param AuthenticationSuccessEvent $authenticationSuccessEvent
   * @return void
   */
  public function success(AuthenticationSuccessEvent $authenticationSuccessEvent)
  {
    if($this->request)
    {
      /** @var UserInterface $user */
      if($user = $authenticationSuccessEvent->getAuthenticationToken()->getUser())
      {
        if($this->authorizationChecker->isGranted("ROLE_ADMIN_ACCESS"))
        {
          $this->request->getSession()->set("austral_language_interface", $user->getLanguage());
        }
      }
    }
  }



}