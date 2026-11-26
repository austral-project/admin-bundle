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

use Austral\AdminBundle\Security\RoleTree;
use Austral\SecurityBundle\EntityManager\RoleEntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Austral Admin Roles Command.
 *
 * Creates the roles of the modules missing in the database (austral:admin:roles), --dry-run to list them only.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminRolesCommand extends Command
{
  /**
   * @param RoleTree          $roleTree
   * @param RoleEntityManager $roles
   */
  public function __construct(private readonly RoleTree $roleTree, private readonly RoleEntityManager $roles)
  {
    parent::__construct();
  }

  /**
   * @return void
   */
  protected function configure(): void
  {
    $this->setDescription('Creates the roles of the admin modules missing in the database')
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Lists the missing roles without creating them');
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);
    $existing = $this->roles->selectRoles();
    $missing = array_diff_key($this->roleTree->roles(), $existing);
    if (!$missing)
    {
      $io->success('All the roles of the modules exist.');

      return Command::SUCCESS;
    }
    $io->table(['Role', 'Name'], array_map(static fn (string $role, string $name): array => [$role, $name], array_keys($missing), $missing));
    if ($input->getOption('dry-run'))
    {
      $io->note(\sprintf('%d roles to create (dry run).', \count($missing)));

      return Command::SUCCESS;
    }
    foreach ($missing as $role => $name)
    {
      $entity = $this->roles->create();
      $entity->setRole($role)->setName($name);
      $this->roles->update($entity, false);
    }
    $this->roles->flush();
    $io->success(\sprintf('%d roles created.', \count($missing)));

    return Command::SUCCESS;
  }
}
