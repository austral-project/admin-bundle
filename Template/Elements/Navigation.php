<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Template\Elements;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\Modules;

/**
 * Austral Navigation.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Navigation
{

  /**
   * @var array
   */
  protected array $navigation = array();

  /**
   * Area constructor.
   */
  public function __construct()
  {

  }

  /**
   * @param Modules $modules
   *
   * @return Navigation
   */
  public function init(Modules $modules): Navigation
  {
    /**
     * @var Module $module
     */
    foreach($modules->getModules() as $url => $module)
    {
      if($module->navigationEnabled() === true && $module->isGranted())
      {
        $this->navigation["{$module->navigationPosition()}-{$url}"] = array(
          "url"         =>  $module->generateUrl(),
          "entitled"    =>  $module->translatePlural(),
          "picto"       =>  $module->getPicto(),
          "modulePath"  =>  $module->getModulePath()
        );
      }
    }
    ksort($this->navigation, SORT_NUMERIC);
    return $this;
  }

  /**
   * @return array
   */
  public function __serialize()
  {
    return $this->navigation;
  }

}