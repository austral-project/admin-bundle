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

namespace Austral\AdminBundle\Tests\Unit\Listener;

use Austral\AdminBundle\Listener\SecurityMailListener;
use Austral\AdminBundle\Tests\Fixtures\Entity\User;
use Austral\SecurityBundle\Event\LoginLinkRequestedEvent;
use Austral\SecurityBundle\Event\PasswordResetRequestedEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class SecurityMailListenerTest extends TestCase
{
  public function testTheLinksAreSentToTheUser(): void
  {
    $mailer = new class implements MailerInterface {
      /** @var list<RawMessage> */
      public array $messages = [];

      public function send(RawMessage $message, ?Envelope $envelope = null): void
      {
        $this->messages[] = $message;
      }
    };
    $loader = new FilesystemLoader();
    $loader->addPath(\dirname(__DIR__, 3).'/src/Resources/views', 'AustralAdmin');
    $twig = new Environment($loader, ['strict_variables' => true]);
    $twig->addExtension(new TranslationExtension());
    $listener = new SecurityMailListener($mailer, $twig, 'admin@austral.dev', 'Austral');
    $user = new User();
    $user->setEmail('jane@austral.dev')->setUsername('jane')->setLanguage('fr');
    $expiresAt = new \DateTimeImmutable('+1 hour');

    $listener->onPasswordResetRequested(new PasswordResetRequestedEvent($user, 'token', 'https://example.com/reset/token', $expiresAt));
    $listener->onLoginLinkRequested(new LoginLinkRequestedEvent($user, 'https://example.com/login/check?hash=x', $expiresAt));

    self::assertCount(2, $mailer->messages);
    [$reset, $link] = $mailer->messages;
    self::assertInstanceOf(Email::class, $reset);
    self::assertInstanceOf(Email::class, $link);
    self::assertSame('jane@austral.dev', $reset->getTo()[0]->getAddress());
    self::assertSame('admin@austral.dev', $reset->getFrom()[0]->getAddress());
    self::assertSame('email.password_reset.subject', $reset->getSubject(), 'No translator: the key.');
    self::assertStringContainsString('https://example.com/reset/token', (string) $reset->getHtmlBody());
    self::assertStringContainsString('https://example.com/login/check?hash=x', (string) $link->getTextBody());
  }
}
