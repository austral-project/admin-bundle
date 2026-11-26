<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Controller;

use Austral\AdminBundle\Page\FormPage;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\Security\CsrfGuard;
use Austral\AdminBundle\View\AdminRenderer;
use Austral\FormBundle\Field\PasswordField;
use Austral\SecurityBundle\Entity\Interfaces\UserInterface;
use Austral\SecurityBundle\Exception\InvalidPasswordResetTokenException;
use Austral\SecurityBundle\LoginLink\LoginLinkRequester;
use Austral\SecurityBundle\Password\PasswordResetManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Austral Security Controller.
 *
 * The pages of the firewall of the admin: login, login link (with a confirmation page, check_post_only), password
 * forgotten and reset. The answers do not tell whether an account exists.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class SecurityController
{
  public const string TOKEN_LOGIN_LINK = 'austral_admin_login_link';
  public const string TOKEN_PASSWORD_FORGOT = 'austral_admin_password_forgot';
  public const string TOKEN_PASSWORD_RESET = 'austral_admin_password_reset';

  /**
   * @param AuthenticationUtils           $authenticationUtils
   * @param AuthorizationCheckerInterface $authorizationChecker
   * @param LoginLinkRequester            $loginLinkRequester
   * @param PasswordResetManager          $passwordResetManager
   * @param CsrfGuard                     $csrf
   * @param AdminUrlGenerator             $urls
   * @param AdminRenderer                 $renderer
   * @param bool                          $passwordResetEnabled
   * @param string                        $passwordPattern      Regular expression of a new password
   */
  public function __construct(
    private readonly AuthenticationUtils $authenticationUtils,
    private readonly AuthorizationCheckerInterface $authorizationChecker,
    private readonly LoginLinkRequester $loginLinkRequester,
    private readonly PasswordResetManager $passwordResetManager,
    private readonly CsrfGuard $csrf,
    private readonly AdminUrlGenerator $urls,
    private readonly AdminRenderer $renderer,
    private readonly bool $passwordResetEnabled = false,
    private readonly string $passwordPattern = PasswordField::MATCH_DEFAULT,
  ) {
  }

  /**
   * The form is checked by form_login of the firewall (check_path: this route).
   *
   * @param Request $request
   *
   * @return Response
   */
  public function login(Request $request): Response
  {
    if ($this->authorizationChecker->isGranted(UserInterface::ROLE_ADMIN_ACCESS))
    {
      return new RedirectResponse($this->urls->route(AdminRouteLoader::ROUTE_INDEX));
    }

    return $this->renderer->render($request, 'login', '@AustralAdmin/security/login.html.twig', [
      'last_username' => $this->authenticationUtils->getLastUsername(),
      'error' => $this->authenticationUtils->getLastAuthenticationError(),
      'login_link' => $this->loginLinkRequester->isEnabled(),
      'password_reset' => $this->passwordResetEnabled,
    ]);
  }

  /**
   * @return never
   */
  public function logout(): never
  {
    throw new \LogicException('The logout of the admin is handled by the firewall: set "logout.path: austral_admin_security_logout" in security.yaml.');
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function loginLink(Request $request): Response
  {
    if (!$this->loginLinkRequester->isEnabled())
    {
      throw new NotFoundHttpException('The login link is not enabled.');
    }
    $sent = false;
    if ($request->isMethod('POST'))
    {
      $this->csrf->check($request, self::TOKEN_LOGIN_LINK);
      $login = $request->request->get('login');
      if (\is_string($login) && '' !== trim($login))
      {
        $this->loginLinkRequester->request($login, $request);
      }
      $sent = true;
    }

    return $this->renderer->render($request, 'login_link', '@AustralAdmin/security/login_link.html.twig', [
      'sent' => $sent,
      'token_id' => self::TOKEN_LOGIN_LINK,
    ]);
  }

  /**
   * Confirmation page of a login link: the firewall (check_post_only) logs in on the POST of this page, a mail
   * scanner opening the link does not use it up.
   *
   * @param Request $request
   *
   * @return Response
   */
  public function loginLinkCheck(Request $request): Response
  {
    if (!$this->loginLinkRequester->isEnabled())
    {
      throw new NotFoundHttpException('The login link is not enabled.');
    }
    $response = $this->renderer->render($request, 'login_link_check', '@AustralAdmin/security/login_link_check.html.twig', [
      'parameters' => array_filter([
        'user' => $request->query->get('user'),
        'expires' => $request->query->get('expires'),
        'hash' => $request->query->get('hash'),
      ], static fn (mixed $value): bool => \is_string($value) && '' !== $value),
    ]);
    $response->headers->set('Referrer-Policy', 'no-referrer');

    return $response;
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function passwordForgot(Request $request): Response
  {
    if (!$this->passwordResetEnabled)
    {
      throw new NotFoundHttpException('The password reset is not enabled.');
    }
    $sent = false;
    if ($request->isMethod('POST'))
    {
      $this->csrf->check($request, self::TOKEN_PASSWORD_FORGOT);
      $login = $request->request->get('login');
      if (\is_string($login) && '' !== trim($login))
      {
        $this->passwordResetManager->request($login, fn (string $token): string => $this->urls->route(AdminRouteLoader::ROUTE_PASSWORD_RESET, ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL));
      }
      $sent = true;
    }

    return $this->renderer->render($request, 'password_forgot', '@AustralAdmin/security/password_forgot.html.twig', [
      'sent' => $sent,
      'token_id' => self::TOKEN_PASSWORD_FORGOT,
    ]);
  }

  /**
   * @param Request $request
   * @param string  $token
   *
   * @return Response
   */
  public function passwordReset(Request $request, string $token): Response
  {
    if (!$this->passwordResetEnabled)
    {
      throw new NotFoundHttpException('The password reset is not enabled.');
    }
    $valid = null !== $this->passwordResetManager->verify($token);
    $errors = [];
    if ($valid && $request->isMethod('POST'))
    {
      $this->csrf->check($request, self::TOKEN_PASSWORD_RESET);
      $password = $request->request->get('password');
      $confirmation = $request->request->get('password_confirmation');
      if (!\is_string($password) || '' === $password || !preg_match($this->passwordPattern, $password))
      {
        $errors[] = 'errors.plainPassword.regex';
      }
      elseif ($password !== $confirmation)
      {
        $errors[] = 'errors.plainPassword.repeat';
      }
      else
      {
        try
        {
          $this->passwordResetManager->reset($token, $password);
          FormPage::flash($request, 'success', 'flash.password_reset');

          return new RedirectResponse($this->urls->route(AdminRouteLoader::ROUTE_LOGIN));
        }
        catch (InvalidPasswordResetTokenException)
        {
          $valid = false;
        }
      }
    }

    $response = $this->renderer->render($request, 'password_reset', '@AustralAdmin/security/password_reset.html.twig', [
      'valid' => $valid,
      'errors' => $errors,
      'token' => $token,
      'token_id' => self::TOKEN_PASSWORD_RESET,
    ], null, $valid ? ($errors ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK) : Response::HTTP_NOT_FOUND);
    $response->headers->set('Referrer-Policy', 'no-referrer');

    return $response;
  }
}
