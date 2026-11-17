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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

/**
 * Austral Authentication Listener.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AuthenticationListener
{

  /**
   * @var Request|null
   */
  protected ?Request $request = null;

  /**
   * @var AuthorizationChecker
   */
  protected AuthorizationChecker $authorizationChecker;

  /**
   * @param AuthorizationChecker $authorizationChecker
   */
  public function __construct(AuthorizationChecker $authorizationChecker)
  {
    $this->authorizationChecker = $authorizationChecker;
  }

  /**
   * @param RequestStack $requestStack
   * @return $this
   */
  public function setRequestStack(RequestStack $requestStack): self
  {
    $this->request = $requestStack->getCurrentRequest();
    return $this;
  }

  /**
   * loginSuccessEvent
   *
   * @param AuthenticationSuccessEvent $authenticationSuccessEvent
   * @return void
   */
  public function success(AuthenticationSuccessEvent $authenticationSuccessEvent): void
  {
    if($this->request)
    {
      /** @var UserInterface<T>|null $user */
      $user = $authenticationSuccessEvent->getAuthenticationToken()->getUser();
      if($user)
      {
        if($this->authorizationChecker->isGranted("ROLE_ADMIN_ACCESS"))
        {
          $this->request->getSession()->set("austral_language_interface", $user->getLanguage());
        }
      }
    }
  }



}