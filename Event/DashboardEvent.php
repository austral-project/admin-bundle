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

use Austral\AdminBundle\Dashboard\DashboardBlock;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Dashboard Event.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DashboardEvent extends Event
{

  const EVENT_AUSTRAL_ADMIN_DASHBOARD = "austral.event.admin.dashboard";

  /**
   * @var DashboardBlock
   */
  private DashboardBlock $dashboardBlock;

  /**
   * FormEvent constructor.
   *
   */
  public function __construct(?DashboardBlock $dashboardBlock = null)
  {
    $this->dashboardBlock = $dashboardBlock ? : new DashboardBlock("master");
  }

  /**
   * @return DashboardBlock
   */
  public function getDashboardBlock(): DashboardBlock
  {
    return $this->dashboardBlock;
  }

  /**
   * @param string $keyname
   * @param DashboardBlock|null $parent
   *
   * @return DashboardBlock
   */
  public function addBlock(string $keyname, DashboardBlock $parent = null): DashboardBlock
  {
    return new DashboardBlock($keyname, $parent ? : $this->dashboardBlock);
  }


}