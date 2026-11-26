<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Austral Csrf Guard.
 *
 * The actions that change data (delete, duplicate, truncate, toggle, sort, POST of an extra action) need the
 * CSRF token of the admin: id "austral_admin", field "_token" (or the header X-CSRF-Token).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class CsrfGuard
{
  public const string TOKEN_ID = 'austral_admin';
  public const string FIELD = '_token';
  public const string HEADER = 'X-CSRF-Token';

  /**
   * @param CsrfTokenManagerInterface $tokenManager
   */
  public function __construct(private readonly CsrfTokenManagerInterface $tokenManager)
  {
  }

  /**
   * @param Request $request
   * @param string  $tokenId
   *
   * @return void
   *
   * @throws AccessDeniedHttpException
   */
  public function check(Request $request, string $tokenId = self::TOKEN_ID): void
  {
    $value = $request->request->get(self::FIELD) ?? $request->headers->get(self::HEADER);
    if (!\is_string($value) || '' === $value || !$this->tokenManager->isTokenValid(new CsrfToken($tokenId, $value)))
    {
      throw new AccessDeniedHttpException('Invalid CSRF token.');
    }
  }

  /**
   * @param string $tokenId
   *
   * @return string
   */
  public function token(string $tokenId = self::TOKEN_ID): string
  {
    return $this->tokenManager->getToken($tokenId)->getValue();
  }
}
