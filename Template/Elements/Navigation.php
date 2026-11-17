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
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Navigation
{

  /**
   * @var array<string, array{url: string|null, entitled: string, picto: string, modulePath: string}>
   */
  protected array $navigation = array();

  /**
   * Area constructor.
   */
  public function __construct()
  {

  }

  /**
   * @param Modules<T> $modules
   *
   * @return $this
   */
  public function init(Modules $modules): self
  {
    /**
     * @var Module<T> $module
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
   * get
   *
   * @return array<string, array{url: string|null, entitled: string, picto: string, modulePath: string}>
   */
  public function get(): array
  {
    return $this->navigation;
  }

}