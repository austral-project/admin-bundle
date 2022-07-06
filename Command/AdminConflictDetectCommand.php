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

use Austral\ToolsBundle\Command\Base\Command;
use Austral\ToolsBundle\Command\Exception\CommandException;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Austral Conflict Detect Command.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AdminConflictDetectCommand extends Command
{

  /**
   * @var string
   */
  protected static $defaultName = 'austral:admin:conflict-detect';

  /**
   * @var string
   */
  protected string $titleCommande = "Conflict detect page";

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setDefinition([
        new InputOption('--service', '-s', InputOption::VALUE_NONE, 'Send service command'),
        new InputOption('--sleep', '', InputOption::VALUE_OPTIONAL, 'Sleep reload command'),
      ])
      ->setDescription($this->titleCommande)
      ->setHelp(<<<'EOF'
The <info>%command.name%</info> conflict detect user

  <info>php %command.full_name%</info>
  <info>php %command.full_name% --service</info>
  <info>php %command.full_name% -s</info>
EOF
      )
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   *
   * @throws CommandException
   * @throws \Doctrine\ORM\NonUniqueResultException
   */
  protected function executeCommand(InputInterface $input, OutputInterface $output)
  {
    if($input->getOption("service")) {
      $sleep = $input->getOption("sleep") ? : 5;
      while(!$this->commandeStop) {
        $this->container->get('austral.admin.conflict_detect')->execute();
        sleep($sleep*1);
      }
    }
    else {
      $this->container->get('austral.admin.conflict_detect')->execute();
    }
  }


}