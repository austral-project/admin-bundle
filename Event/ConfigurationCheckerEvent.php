<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Event;

use Austral\AdminBundle\Configuration\ConfigurationChecker;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral ConfigurationChecker Event.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ConfigurationCheckerEvent extends Event
{

  const EVENT_AUSTRAL_ADMIN_CONFIGURATION_CHECKER = "austral.event.admin.configuration_checker";

  /**
   * @var ConfigurationChecker
   */
  private ConfigurationChecker $configurationChecker;

  /**
   * FormEvent constructor.
   *
   */
  public function __construct(?ConfigurationChecker $configurationChecker = null)
  {
    $this->configurationChecker = $configurationChecker ? : new ConfigurationChecker("master");
  }

  /**
   * @return ConfigurationChecker
   */
  public function getConfigurationChecker(): ConfigurationChecker
  {
    return $this->configurationChecker;
  }

  /**
   * @param string $keyname
   * @param ConfigurationChecker|null $parent
   *
   * @return ConfigurationChecker
   */
  public function addConfiguration(string $keyname, ConfigurationChecker $parent = null): ConfigurationChecker
  {
    return new ConfigurationChecker($keyname, $parent ? : $this->configurationChecker);
  }


}