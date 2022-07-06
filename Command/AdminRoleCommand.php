<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Command;

use Austral\AdminBundle\Module\Module;

use Austral\SecurityBundle\Entity\Interfaces\RoleInterface;
use Austral\SecurityBundle\EntityManager\RoleEntityManager;

use Austral\ToolsBundle\Command\Base\Command;
use Austral\ToolsBundle\Command\Exception\CommandException;

use Austral\EntityBundle\Entity\EntityInterface;

use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use function Symfony\Component\String\u;

/**
 * Austral Roles Command.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AdminRoleCommand extends Command
{

  /**
   * @var string
   */
  protected static $defaultName = 'austral:admin:roles';

  /**
   * @var string
   */
  protected string $titleCommande = "Create or update Module Roles";

  /**
   * @var RoleEntityManager|null
   */
  protected ?RoleEntityManager $roleEntityManager;

  /**
   * @var array
   */
  protected array $rolesExists;

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setDefinition([
        new InputOption('--clean', '-c', InputOption::VALUE_NONE, 'Delete all roles'),
        new InputOption('--generate', '-g', InputOption::VALUE_NONE, 'Generate automatically manager roles'),
      ])
      ->setDescription($this->titleCommande)
      ->setHelp(<<<'EOF'
The <info>%command.name%</info> command to create or update modules roles

  <info>php %command.full_name% --clean</info>
  <info>php %command.full_name% --generate</info>
  <info>php %command.full_name% --clean --generate</info>
  
  <info>php %command.full_name% -c</info>
  <info>php %command.full_name% -g</info>
  <info>php %command.full_name% -c -g</info>
EOF
      )
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   *
   * @throws CommandException
   * @throws Exception
   * @throws NonUniqueResultException
   */
  protected function executeCommand(InputInterface $input, OutputInterface $output)
  {
    $this->roleEntityManager = $this->container->get("austral.entity_manager.role");
    if($input->getOption("clean"))
    {
      $classMetaData = $this->roleEntityManager->getDoctrineEntityManager()->getClassMetadata($this->roleEntityManager->getClass());
      $connection = $this->roleEntityManager->getDoctrineEntityManager()->getConnection();
      $dbPlatform = $connection->getDatabasePlatform();
      $connection->beginTransaction();
      $isMysql = $connection->getDriver() instanceof Driver;
      try {
        if($isMysql) {
          $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        }
        $q = $dbPlatform->getTruncateTableSql($classMetaData->getTableName(), true);
        $connection->executeStatement($q);
        if($isMysql) {
          $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        }
        $connection->commit();
        $this->viewMessage("Roles Clean successfully !!!", "success");
      }
      catch (Exception $e) {
        $connection->rollback();
        $this->viewMessage("Roles Clean error -> {$e->getMessage()} !!!", "error");
      }
    }

    if($input->getOption("generate"))
    {
      $this->rolesExists = $this->roleEntityManager->selectRoles();
      $nbRolesAdd = 0;
      $nbRolesUpdate = 0;
      try {
        $modules = $this->container->get('austral.admin.modules')->init()->getModules();
        /** @var Module $module */
        foreach($modules as $module)
        {
          if($moduleKey = $module->getModulePath())
          {
            $securityKey = u("ROLE_$moduleKey")->snake()->upper()->__toString();
            if($actionName = $module->getActionName())
            {
              $actionName = $actionName != "index" ? " - {$actionName}" : "";
              $this->addRole("{$module->getName()}{$actionName}", $securityKey, $nbRolesAdd, $nbRolesUpdate);
            }
            elseif($entityActions = $module->getPathActions())
            {
              foreach ($entityActions as $actionKey => $action)
              {
                $this->addRole("{$module->getName()} - {$actionKey}", $securityKey.($actionKey != "list" ? "_".strtoupper($actionKey) : ""), $nbRolesAdd, $nbRolesUpdate);
              }
            }
          }
        }
        if($nbRolesAdd > 0 || $nbRolesUpdate > 0)
        {
          $this->roleEntityManager->flush();
        }
        $this->viewMessage("Roles Generates successfully [Role Add : {$nbRolesAdd}] - [Role Update : {$nbRolesUpdate}] !!!", "success");
      }
      catch (Exception $e) {

        $this->viewMessage("Roles Generate error -> {$e->getMessage()} !!!", "error");
      }
    }
  }

  /**
   * @param string $roleName
   * @param string $role
   * @param int $nbRolesAdd
   * @param int $nbRolesUpdate
   *
   * @return void
   */
  protected function addRole(string $roleName, string $role, int &$nbRolesAdd, int &$nbRolesUpdate)
  {
    $roleName = str_replace("_", " ", $roleName);
    $roleName = preg_replace('/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]/', ' $0', $roleName);
    $roleName = ucwords(strtolower($roleName));
    if(!array_key_exists($role, $this->rolesExists))
    {
      $objectRole = $this->roleEntityManager->create(array(
          'name'  =>  $roleName,
          "role"  =>  $role
        )
      );
      $nbRolesAdd++;
    }
    else
    {
      /** @var RoleInterface|EntityInterface $objectRole */
      $objectRole = $this->rolesExists[$role];
      $objectRole->setName($roleName);
      $objectRole->setRole($role);
      $nbRolesUpdate++;
    }
    $this->roleEntityManager->update($objectRole, false);
  }


}