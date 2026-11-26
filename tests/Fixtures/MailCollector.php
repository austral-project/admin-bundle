<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Austral\AdminBundle\Tests\Fixtures;

use Austral\SecurityBundle\Event\LoginLinkRequestedEvent;
use Austral\SecurityBundle\Event\PasswordResetRequestedEvent;

/**
 * Keeps the links of the security events (instead of a mail).
 */
final class MailCollector
{
  /**
   * @var list<string>
   */
  public array $passwordResetUrls = [];

  /**
   * @var list<string>
   */
  public array $loginLinkUrls = [];

  public function onPasswordReset(PasswordResetRequestedEvent $event): void
  {
    $this->passwordResetUrls[] = $event->getUrl();
  }

  public function onLoginLink(LoginLinkRequestedEvent $event): void
  {
    $this->loginLinkUrls[] = $event->getUrl();
  }
}
