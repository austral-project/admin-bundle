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


use Austral\AdminBundle\Configuration\AdminConfiguration;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\EntityManager\EntityManager;
use Austral\EntityTranslateBundle\Entity\Interfaces\EntityTranslateMasterInterface;
use Austral\FormBundle\Event\FormEvent;
use Austral\FormBundle\Mapper\Fieldset;
use Austral\FormBundle\Field as Field;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;

/**
 * Austral Form Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class FormListener
{

  /**
   * @var Request|null
   */
  protected ?Request $request = null;

  /**
   * @var Router
   */
  protected Router $router;

  /**
   * @var EntityManager
   */
  protected EntityManager $entityManager;

  /**
   * @var AdminConfiguration|null
   */
  protected ?AdminConfiguration $adminConfiguration = null;

  /**
   * FormListener constructor.
   *
   * @param RequestStack $request
   * @param Router $router
   * @param EntityManager $entityManager
   * @param AdminConfiguration|null $adminConfiguration
   */
  public function __construct(RequestStack $request, Router $router, EntityManager $entityManager, ?AdminConfiguration $adminConfiguration)
  {
    $this->request = $request->getCurrentRequest();
    $this->router = $router;
    $this->adminConfiguration = $adminConfiguration;
    $this->entityManager = $entityManager;
  }

  /**
   * @param FormEvent $formEvent
   *
   * @throws \ReflectionException
   */
  public function formAddAutoFields(FormEvent $formEvent)
  {
    if(AustralTools::usedImplements($formEvent->getFormMapper()->getObject(),EntityTranslateMasterInterface::class) && $this->adminConfiguration)
    {
      if($this->adminConfiguration->get('language.enabled_multi'))
      {
        /** @var EntityTranslateMasterInterface|EntityInterface $object */
        $object = $formEvent->getFormMapper()->getObject();
        $languagesAvailable = array();
        $objectLanguages = $this->entityManager->getRepository(get_class($object))->selectArrayLanguages($object);
        foreach($objectLanguages as $language)
        {
          $languagesAvailable[$language["language"]] = $language["language"];
        }
        $formEvent->getFormMapper()->addFieldset("fieldset.right")
          ->setPositionName(Fieldset::POSITION_RIGHT)
          ->add(Field\TemplateField::create("selectLanguage", "@AustralAdmin/Form/Components/select-language.html.twig", array(), array(
                "language" => array(
                  "list"        =>  $this->adminConfiguration->get("language.list"),
                  "current"     =>  $object->getLanguageCurrent(),
                  "available"   =>  $languagesAvailable
                )
              )
            )
          )
        ->end();
      }
    }
  }

}