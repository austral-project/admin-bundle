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

use Austral\AdminBundle\Configuration\ConfigurationCheck;
use Austral\AdminBundle\Event\ConfigurationCheckEvent;
use Austral\SecurityBundle\LoginLink\LoginLinkRequester;

/**
 * Austral Configuration Check Listener.
 *
 * The checks of the admin: HTTPS, environment, debug, login link and mails.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ConfigurationCheckListener
{
  /**
   * @param string                  $environment
   * @param bool                    $debug
   * @param bool                    $mailEnabled        A mail sender is configured
   * @param LoginLinkRequester|null $loginLinkRequester
   */
  public function __construct(
    private readonly string $environment,
    private readonly bool $debug,
    private readonly bool $mailEnabled,
    private readonly ?LoginLinkRequester $loginLinkRequester = null,
  ) {
  }

  /**
   * @param ConfigurationCheckEvent $event
   *
   * @return void
   */
  public function __invoke(ConfigurationCheckEvent $event): void
  {
    $event->add(ConfigurationCheck::check('security', 'https', $event->getRequest()->isSecure(), 'configuration.https.entitled', 'configuration.https.description', ConfigurationCheck::STATUS_ERROR))
      ->add(new ConfigurationCheck('environment', 'env', 'prod' === $this->environment ? ConfigurationCheck::STATUS_SUCCESS : ConfigurationCheck::STATUS_WARNING, 'configuration.env.entitled', 'configuration.env.description', $this->environment))
      ->add(ConfigurationCheck::check('environment', 'debug', !$this->debug, 'configuration.debug.entitled', 'configuration.debug.description'))
      ->add(ConfigurationCheck::check('security', 'mail', $this->mailEnabled, 'configuration.mail.entitled', 'configuration.mail.description'))
      ->add(new ConfigurationCheck('security', 'login_link', $this->loginLinkRequester?->isEnabled() ? ConfigurationCheck::STATUS_SUCCESS : ConfigurationCheck::STATUS_INFO, 'configuration.login_link.entitled', 'configuration.login_link.description'));
  }
}
