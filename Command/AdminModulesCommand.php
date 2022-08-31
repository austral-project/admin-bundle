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
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;
use Austral\ToolsBundle\Command\Base\Command;
use Austral\ToolsBundle\Command\Exception\CommandException;

use Composer\Autoload\ClassLoader;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Austral Modules Command.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AdminModulesCommand extends Command
{

  /**
   * @var string
   */
  protected static $defaultName = 'austral:admin:modules';

  /**
   * @var string
   */
  protected string $titleCommande = "Create Module file";

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setDefinition([
        new InputOption('--generate', '-g', InputOption::VALUE_NONE, 'Generate automatically manager roles'),
      ])
      ->setDescription($this->titleCommande)
      ->setHelp(<<<'EOF'
The <info>%command.name%</info> command to create module file

  <info>php %command.full_name% --generate</info>
  
  <info>php %command.full_name% -g</info>
EOF
      )
    ;
  }

  /**
   * @var ClassLoader
   */
  protected ClassLoader $composerLoader;

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   *
   * @throws Exception
   */
  protected function executeCommand(InputInterface $input, OutputInterface $output)
  {
    if($input->getOption("generate"))
    {
      $this->composerLoader = require 'vendor/autoload.php';

      $mapping = $this->container->get("austral.entity.mapping");

      /** @var Module $module */
      foreach ($this->container->get("austral.admin.modules")->init()->getModules() as $module)
      {
        if(!class_exists($module->getAdminClass()))
        {
          $entityManager = $module->getEntityManager();

          $filterMapperLines = array();
          $listMapperLines = array(
            '->getSection("default")',
            '  ->buildDataHydrate(function(DataHydrateORM $dataHydrate) {',
            '    $dataHydrate->addQueryBuilderPaginatorClosure(function(QueryBuilder $queryBuilder) {',
            '      return $queryBuilder->orderBy("root.id", "ASC");',
            '    });',
            '  })'
          );
          $formMapperLines = array(
            '->addFieldset("fieldset.generalInformation")'
          );
          /** @var array $field */
          foreach($entityManager->getFieldsMappingAll() as $key => $field)
          {
            if($field["type"] === "string" && $key !== "id")
            {
              $filterMapperLines[] = sprintf('  ->add(new FilterType\StringType("%s"))', $field["fieldName"]);
              $listMapperLines[] = sprintf('  ->addColumn(new Column\Value("%s"))', $field["fieldName"]);
              if($fileMapping = $mapping->getFieldsMappingByFieldname($entityManager->getClass(), FieldFileMapping::class, $field["fieldName"]))
              {
                $formMapperLines[] = sprintf('  ->add(Field\UploadField::create("%s"))', $field["fieldName"]);
                $formMapperLines[] = sprintf('  ->addPopin("popup-editor-%s", "%s", array(', $field["fieldName"], $field["fieldName"]);
                $formMapperLines[] = sprintf('    "button"  =>  array(');
                $formMapperLines[] = sprintf('      "entitled"      =>  "actions.picture.edit",');
                $formMapperLines[] = sprintf('      "picto"         =>  "",');
                $formMapperLines[] = sprintf('      "class"         =>  "button-action"');
                $formMapperLines[] = sprintf('    ),');
                $formMapperLines[] = sprintf('    "popin"  =>  array(');
                $formMapperLines[] = sprintf('      "id"            =>  "upload",');
                $formMapperLines[] = sprintf('      "template"      =>  "uploadEditor",');
                $formMapperLines[] = sprintf( '   )');
                $formMapperLines[] = sprintf('  ))');
                $formMapperLines[] = sprintf('->end()');
              }
              else
              {
                $formMapperLines[] = sprintf('  ->add(Field\TextField::create("%s"))', $field["fieldName"]);
              }
            }
            elseif($field["type"] === "text")
            {
              $formMapperLines[] = sprintf('  ->add(Field\WysiwygField::create("%s"))', $field["fieldName"]);
            }
            elseif($field["type"] === "float")
            {
              $formMapperLines[] = sprintf('  ->add(Field\NumberField::create("%s"))', $field["fieldName"]);
            }
            elseif($field["type"] === "integer")
            {
              $formMapperLines[] = sprintf('  ->add(Field\IntegerField::create("%s"))', $field["fieldName"]);
            }
            elseif(array_key_exists("joinColumns", $field)) {
              $formMapperLines[] = sprintf('  ->add(Field\EntityField::create("%s", "%s"))', $field["fieldName"], $field["targetEntity"]);
            }
          }

          $listMapperLines[] = "->end()";
          $formMapperLines[] = "->end()";

          $filePath = $this->getFilePathByClassname($module->getAdminClass());
          $searchReplaceValues = array(
            "##php##"                 =>  "<?php",
            "##NAMESPACE##"           =>  $this->getNamespace($module->getAdminClass()),
            "##NAME##"                =>  $module->getName(),
            "##CLASSNAME##"           =>  $this->getClassnameShort($module->getAdminClass()),
            "##FILTER_MAPPER##"       =>  implode("\n      ", $filterMapperLines),
            "##LIST_MAPPER##"         =>  implode("\n      ", $listMapperLines),
            "##FORM_MAPPER##"         =>  implode("\n      ", $formMapperLines),
          );

          $fileContent = file_get_contents(__DIR__."/../Skeleton/Admin/Admin.php");
          $fileContent = str_replace(array_keys($searchReplaceValues), array_values($searchReplaceValues), $fileContent);
          file_put_contents($filePath, $fileContent);
        }
      }
    }
  }

  /**
   * @param string $classname
   *
   * @return string|null
   */
  protected function getFilePathByClassname(string $classname): ?string
  {
    $classnameExplode = explode("\\", $classname);
    $classnameSearchHydrate = array();
    $filePath = null;
    foreach($classnameExplode as $classnameElement)
    {
      $classnameSearchHydrate[] = $classnameElement;
      $classnameSearch = implode("\\", $classnameSearchHydrate)."\\";
      if(array_key_exists($classnameSearch, $this->composerLoader->getPrefixesPsr4()))
      {
        $filePath = AustralTools::first($this->composerLoader->getPrefixesPsr4()[$classnameSearch]);
      }
      elseif($filePath)
      {
        $filePath .= "/".$classnameElement;
      }
    }
    if($filePath)
    {
      $filePath .= ".php";
    }
    return $filePath;
  }

  /**
   * @param string $classname
   *
   * @return string|null
   */
  protected function getNamespace(string $classname): ?string
  {
    $classnameExplode = explode("\\", $classname);
    array_pop($classnameExplode);
    return implode("\\", $classnameExplode);
  }

  /**
   * @param string $classname
   *
   * @return string|null
   */
  protected function getClassnameShort(string $classname): ?string
  {
    $classnameExplode = explode("\\", $classname);
    return AustralTools::last($classnameExplode);
  }


}