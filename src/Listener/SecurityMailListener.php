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

use Austral\SecurityBundle\Entity\Interfaces\UserInterface;
use Austral\SecurityBundle\Event\LoginLinkRequestedEvent;
use Austral\SecurityBundle\Event\PasswordResetRequestedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Austral Security Mail Listener.
 *
 * Sends the password reset link and the login link (events of security-bundle). Registered when symfony/mailer
 * is installed and "austral_admin.mail.from" is set.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class SecurityMailListener
{
  /**
   * @param MailerInterface          $mailer
   * @param Environment              $twig
   * @param string                   $from
   * @param string|null              $fromName
   * @param TranslatorInterface|null $translator
   */
  public function __construct(
    private readonly MailerInterface $mailer,
    private readonly Environment $twig,
    private readonly string $from,
    private readonly ?string $fromName = null,
    private readonly ?TranslatorInterface $translator = null,
  ) {
  }

  /**
   * @param PasswordResetRequestedEvent $event
   *
   * @return void
   */
  public function onPasswordResetRequested(PasswordResetRequestedEvent $event): void
  {
    $this->send($event->getUser(), 'email.password_reset.subject', '@AustralAdmin/email/password_reset.html.twig', [
      'url' => $event->getUrl(),
      'expires_at' => $event->getExpiresAt(),
    ]);
  }

  /**
   * @param LoginLinkRequestedEvent $event
   *
   * @return void
   */
  public function onLoginLinkRequested(LoginLinkRequestedEvent $event): void
  {
    $this->send($event->getUser(), 'email.login_link.subject', '@AustralAdmin/email/login_link.html.twig', [
      'url' => $event->getUrl(),
      'expires_at' => $event->getExpiresAt(),
    ]);
  }

  /**
   * @param UserInterface        $user
   * @param string               $subject  Translation key
   * @param string               $template
   * @param array<string, mixed> $vars
   *
   * @return void
   */
  private function send(UserInterface $user, string $subject, string $template, array $vars): void
  {
    $locale = $user->getLanguage();
    $html = $this->twig->render($template, $vars + ['user' => $user, 'locale' => $locale]);
    $email = new Email()
      ->from(new Address($this->from, $this->fromName ?? ''))
      ->to(new Address($user->getEmail()))
      ->subject($this->translator?->trans($subject, [], 'austral', $locale) ?? $subject)
      ->html($html)
      ->text(trim(html_entity_decode(strip_tags($html))));
    $this->mailer->send($email);
  }
}
