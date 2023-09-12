<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Admin;

use Austral\AdminBundle\Admin\Event\ListAdminEvent;
use Austral\AdminBundle\Configuration\ConfigurationChecker;
use Austral\AdminBundle\Configuration\ConfigurationCheckerValue;
use Austral\AdminBundle\Event\ConfigurationCheckerEvent;
use Austral\ToolsBundle\AustralTools;

/**
 * Configuration Status Admin .
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ConfigurationStatusAdmin extends Admin
{

  /**
   * @param ListAdminEvent $listAdminEvent
   */
  public function index(ListAdminEvent $listAdminEvent)
  {
    $configurationCheckerEvent = new ConfigurationCheckerEvent();
    $subConfigurationChecker = $configurationCheckerEvent->addConfiguration("dirs")
      ->setName("configuration.check.dirs.title")
      ->setIsTranslatable(true)
      ->setDescription("configuration.check.dirs.description");

    $configurationCheckerEvent->addConfiguration("modules")
      ->setName("configuration.check.modules.title")
      ->setIsTranslatable(true)
      ->setDescription("configuration.check.modules.description");

    /*
     * Check Dirs
     */
    $filepermsSuccess = array('0775', "0777");
    $projectDir = $this->container->getParameter("kernel.project_dir");
    $publicDir = $this->container->hasParameter("austral.file.public.path") ? $this->container->getParameter("austral.file.public.path") : "public";
    $dirsToChecked = array(
      "{$publicDir}"  => array("uploads", "thumbnail"),
      "var"     => array("log", "cache", "sessions"),
    );
    foreach($dirsToChecked as $categDirname => $dirnames)
    {
      foreach($dirnames as $dirname)
      {
        $configurationCheckerDirname = new ConfigurationChecker($dirname);
        $configurationCheckerDirname->setParent($subConfigurationChecker);

        $path = AustralTools::join($projectDir, $categDirname, $dirname);
        $configurationCheckerValue = new ConfigurationCheckerValue("path", $configurationCheckerDirname);
        $configurationCheckerValue->setName("configuration.check.dirs.path.entitled")
          ->setIsTranslatable(true)
          ->setType(ConfigurationCheckerValue::$TYPE_STRING)
          ->setValue($path);

        if(file_exists($path)) {
          $isReadable = is_readable($path);
          $isWritable = is_writable($path);
          $fileperms = substr(sprintf('%o', fileperms($path)), -4);

          $configurationCheckerValue = new ConfigurationCheckerValue("readable", $configurationCheckerDirname);
          $configurationCheckerValue->setName("configuration.check.dirs.readable.entitled")
            ->setIsTranslatable(true)
            ->setIsTranslatableValue(true)
            ->setType(ConfigurationCheckerValue::$TYPE_CHECKED)
            ->setStatus($isReadable ? ConfigurationCheckerValue::$STATUS_SUCCESS : ConfigurationCheckerValue::$STATUS_ERROR)
            ->setValue($isReadable ? "configuration.check.choices.enabled" : "configuration.check.choices.disabled");

          $configurationCheckerValue = new ConfigurationCheckerValue("writable", $configurationCheckerDirname);
          $configurationCheckerValue->setName("configuration.check.dirs.writable.entitled")
            ->setIsTranslatable(true)
            ->setIsTranslatableValue(true)
            ->setType(ConfigurationCheckerValue::$TYPE_CHECKED)
            ->setStatus($isWritable ? ConfigurationCheckerValue::$STATUS_SUCCESS : ConfigurationCheckerValue::$STATUS_ERROR)
            ->setValue($isWritable ? "configuration.check.choices.enabled" : "configuration.check.choices.disabled");

          $configurationCheckerValue = new ConfigurationCheckerValue("fileperms", $configurationCheckerDirname);
          $configurationCheckerValue->setName("configuration.check.dirs.fileperms.entitled")
            ->setIsTranslatable(true)
            ->setType(ConfigurationCheckerValue::$TYPE_CHECKED)
            ->setStatus(in_array($fileperms, $filepermsSuccess) ? ConfigurationCheckerValue::$STATUS_SUCCESS : ConfigurationCheckerValue::$STATUS_ERROR)
            ->setValue($fileperms);
        }
        else
        {
          $configurationCheckerValue = new ConfigurationCheckerValue("notExist", $configurationCheckerDirname);
          $configurationCheckerValue->setName("configuration.check.dirs.notExist.entitled")
            ->setIsTranslatable(true)
            ->setIsTranslatableValue(true)
            ->setType(ConfigurationCheckerValue::$TYPE_CHECKED)
            ->setStatus(ConfigurationCheckerValue::$STATUS_ERROR)
            ->setValue("configuration.check.choices.notExist");
        }
      }
    }
    $this->container->get('event_dispatcher')->dispatch($configurationCheckerEvent, ConfigurationCheckerEvent::EVENT_AUSTRAL_ADMIN_CONFIGURATION_CHECKER);
    $listAdminEvent->getTemplateParameters()->setPath("@AustralAdmin/ConfigurationStatus/index.html.twig");
    $listAdminEvent->getTemplateParameters()->addParameters("list", array(
      "configurationChecker"  =>  $configurationCheckerEvent->getConfigurationChecker(),
      "translateDomain"     =>  "austral"
    ));
  }

}