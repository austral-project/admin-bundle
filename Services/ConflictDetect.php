<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Services;

use Austral\EntityFileBundle\File\Link\Generator;
use Austral\NotifyBundle\Mercure\Mercure;
use Austral\NotifyBundle\Notification\Push;
use Austral\SecurityBundle\EntityManager\UserEntityManager;
use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Twig\Environment;

/**
 * Austral Conflict Detect.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
Class ConflictDetect
{

  /**
   * @var Mercure|null
   */
  protected ?Mercure $mercure;

  /**
   * @var Push|null
   */
  protected ?Push $push;

  /**
   * @var Environment
   */
  protected Environment $twig;

  /**
   * @var Generator
   */
  protected Generator $linkGenerator;

  /**
   * @var array
   */
  protected array $topicsConflict;
  
  /**
   * @var array|ArrayCollection
   */
  protected $user;

  /**
   * @param UserEntityManager $userEntityManager
   * @param Environment $twig
   * @param Generator $linkGenerator
   * @param Mercure|null $mercure
   * @param Push|null $push
   * Constructor
   */
  public function __construct(UserEntityManager $userEntityManager, Environment $twig, Generator $linkGenerator, ?Mercure $mercure = null, ?Push $push = null)
  {
    $this->mercure = $mercure;
    $this->push = $push;
    $this->user = $userEntityManager->selectAll("id", "asc", function(QueryBuilder $queryBuilder) {
      $queryBuilder->indexBy("root", "root.id")
        ->where("root.typeUser != :userType")
        ->setParameter("userType", "user");
    });
    $this->twig = $twig;
    $this->linkGenerator = $linkGenerator;
    $this->topicsConflict = array();
  }

  public function execute()
  {
    if($this->mercure && $this->push)
    {
      foreach($this->mercure->listSubscribesByTopics() as $topic => $subscriptions)
      {
        if(strpos($topic, "/create/form") === false)
        {
          preg_match("^.*\/(.*)\/form(\/|)^", $topic, $matches);
          if($matches && (count($subscriptions) > 1))
          {
            $this->topicsConflict[$topic] = true;
            $users = array();
            foreach($subscriptions as $subscription)
            {
              if($userId = AustralTools::getValueByKey(AustralTools::getValueByKey($subscription, "payload", array()), "user-id", null))
              {
                if(array_key_exists($userId, $this->user))
                {
                  $users[] = array(
                    "firstname"     =>  $this->user[$userId]->getFirstname(),
                    "lastname"      =>  $this->user[$userId]->getLastname(),
                    "avatar"        =>  $this->linkGenerator->image($this->user[$userId], "avatar", "original", "resize", 100, 100),
                    "avatarColor"   =>  $this->user[$userId]->getAvatarColor()
                  );
                }
              }
            }
            $template = $this->twig->render("@AustralDesign/Components/Notification/multi-user.html.twig", array(
              "users" =>  $users
            ));
            $this->push->add(Push::TYPE_MERCURE, array('topics'=>array($this->mercure->topicWithoutDomain($topic)), "values" => array(
              'type'            =>  "multi-user",
              "template"        =>  $template
            )))->push();
          }
          elseif(array_key_exists($topic, $this->topicsConflict))
          {
            unset($this->topicsConflict[$topic]);
            $this->push->add(Push::TYPE_MERCURE, array('topics'=>array($this->mercure->topicWithoutDomain($topic)), "values" => array(
              'type'            =>  "multi-user-finish",
            )))->push();
          }
        }
      }
    }

  }

}