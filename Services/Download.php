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

use Austral\AdminBundle\Configuration\AdminConfiguration;
use Austral\Contracts\Entity\EntityInterface;
use Austral\ListBundle\Column\Interfaces\ColumnInterface;
use Austral\ListBundle\Mapper\ListMapper;
use Austral\ListBundle\Row\Row;
use Austral\ListBundle\Section\Section;
use Austral\ToolsBundle\AustralTools;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Symfony\Component\String\u;

/**
 * Austral Download.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
Class Download
{

  /**
   * @var AdminConfiguration
   */
  protected AdminConfiguration $adminConfiguration;

  /**
   * @var ListMapper<T>|null
   */
  protected ?ListMapper $listMapper = null;

  /**
   * @var TranslatorInterface
   */
  protected TranslatorInterface $translator;

  /**
   * @var string|null
   */
  protected ?string $format = null;

  /**
   * @var string|null
   */
  protected ?string $extension = null;

  /**
   * @var string|null
   */
  protected ?string $filename = null;

  /**
   * @var string|null
   */
  protected ?string $contentFile = null;

  /**
   * @var string|null
   */
  protected ?string $contentType = null;

  /**
   * Download constructor
   *
   * @param AdminConfiguration $adminConfiguration
   * @param TranslatorInterface $translator
   */
  public function __construct(AdminConfiguration $adminConfiguration, TranslatorInterface $translator)
  {
    $this->adminConfiguration = $adminConfiguration;
    $this->translator = $translator;
  }

  /**
   * @param ListMapper<T> $listMapper
   *
   * @return $this
   */
  public function setListMapper(ListMapper $listMapper): self
  {
    $this->listMapper = $listMapper;
    return $this;
  }

  /**
   * @param string $format
   *
   * @return $this
   * @throws Exception
   */
  public function setFormat(string $format): self
  {
    $this->format = $format;
    $mimeTypes = new MimeTypes();
    $this->contentType = AustralTools::first($mimeTypes->getMimeTypes($format));
    $this->extension = $format;
    $generateByFormat = "{$this->format}Generate";
    if(!method_exists($this, $generateByFormat))
    {
      throw new Exception("The format {$this->format} is not configured");
    }
    return $this;
  }

  /**
   * @return string
   */
  public function getFilename(): string
  {
    $now = new \DateTime();
    $before = $this->adminConfiguration->get('download.title_before');
    $before = $before ? $before." - " : "";
    $filename = u($this->filename ?? "")->lower()->toString();
    $filename = AustralTools::removeAccents($filename);
    return "{$before}{$filename} - {$now->format("Y-m-d")}.{$this->extension}";
  }

  /**
   * @param string $filename
   *
   * @return $this
   */
  public function setFilename(string $filename): self
  {
    $this->filename = $filename;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getContentFile(): ?string
  {
    return $this->contentFile;
  }

  /**
   * @return string|null
   */
  public function getContentType(): ?string
  {
    return $this->contentType;
  }

  /**
   * generate
   *
   * @return $this
   */
  public function generate(): self
  {
    $generateByFormat = "{$this->format}Generate";
    return $this->$generateByFormat();
  }

  /**
   * push
   *
   * @return void
   */
  public function push(): void
  {
    $generateByFormat = "{$this->format}Push";
    $this->$generateByFormat();
  }


  /**
   * @var string
   */
  protected string $jsonValues = "";

  /**
   * @return $this
   */
  protected function jsonGenerate(): self
  {
    $returnValues = array();

    if($this->listMapper)
    {
      /** @var Section<T> $section */
      foreach($this->listMapper->getSections() as $section)
      {
        $headerColumns = array();
        /** @var ColumnInterface<T> $headerColumn */
        foreach($section->headerColumns() as $headerColumn)
        {
          $headerColumns[$headerColumn->getFieldname()] = $this->translate($headerColumn->getEntitled(), $headerColumn->getTranslateParameters());
        }
        $valuesColumns = array();
        /** @var Row<T> $row */
        foreach ($section->rows() as $row) {
          $valueColumns = array();
          /** @var ColumnInterface<T> $column */
          foreach($row->columns() as $column)
          {
            $valueColumns[$column->getFieldname()] = $this->getValueByColumn($column, true);
          }
          $valuesColumns[] = $valueColumns;
        }
        $returnValues[$section->getKeyname()] = array(
          "header"  =>  $headerColumns,
          "values"  =>  $valuesColumns
        );
      }
      $jsonValues = json_encode(count($returnValues) == 1 ? AustralTools::first($returnValues) : $returnValues);
      $this->jsonValues = $jsonValues ?: "{}";
    }

    return $this;
  }

  /**
   * jsonPush
   *
   * @return void
   */
  protected function jsonPush(): void
  {
    $file = fopen("php://output", 'w');
    if($file && $this->jsonValues !== "")
    {
      echo $this->jsonValues;
      fclose($file);
    }
  }


  /**
   * @var array<int, mixed>
   */
  protected array $csvValues = array();

  /**
   * @return $this
   */
  protected function csvGenerate(): self
  {
    if($this->listMapper)
    {
      /** @var Section<T> $section */
      foreach($this->listMapper->getSections() as $section)
      {
        $headerColumns = array();
        /** @var ColumnInterface<T> $headerColumn */
        foreach($section->headerColumns() as $headerColumn)
        {
          $headerColumns[] = $this->translate($headerColumn->getEntitled(), $headerColumn->getTranslateParameters());
        }
        $this->csvValues[] = $headerColumns;
        /** @var Row<T> $row */
        foreach ($section->rows() as $row) {
          $valueColumns = array();
          /** @var ColumnInterface<T> $column */
          foreach($row->columns() as $column)
          {
            $valueColumns[] = $this->getValueByColumn($column);
          }
          $this->csvValues[] = $valueColumns;
        }
      }
    }
    return $this;
  }

  /**
   * csvPush
   *
   * @return void
   */
  protected function csvPush(): void
  {
    ob_start();
    $file = fopen("php://output", 'w');
    if($file)
    {
      foreach($this->csvValues as $values)
      {
        fputcsv($file, $values);
      }
      fclose($file);
    }
  }



  /**
   * @var Xlsx|null
   */
  protected ?Xlsx $xlsxValues = null;

  /**
   * @return $this
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  protected function xlsxGenerate(): self
  {
    if($this->listMapper)
    {
      $spreadsheet = new Spreadsheet();
      $spreadsheet->getProperties()->setCreator($this->adminConfiguration->get('download.creator'))->setTitle($this->getFilename());

      $sheet = 0;
      /** @var Section<T> $section */
      foreach($this->listMapper->getSections() as $section)
      {
        if($sheet > 0) {
          $spreadsheet->createSheet($sheet);
          $spreadsheet->setActiveSheetIndex($sheet);
        }
        $row = 1;
        $col = 0;
        $spreadsheet->getActiveSheet()->setTitle($this->translate($section->getTitle() ?? ""));
        /** @var ColumnInterface<T> $headerColumn */
        foreach($section->headerColumns() as $headerColumn)
        {
          $colName = $this->colName($col);
          $spreadsheet->getActiveSheet()->getColumnDimension($colName)->setAutoSize(true);

          $value = $this->translate($headerColumn->getEntitled(), $headerColumn->getTranslateParameters());
          $spreadsheet->getActiveSheet()->setCellValue($colName.$row, $value);
          $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(40);
          $col++;
        }

        $row++;
        /** @var Row<T> $rowValues */
        foreach ($section->rows() as $rowValues) {
          /** @var ColumnInterface<T> $column */
          foreach($rowValues->columns() as $col => $column)
          {
            $colName = $this->colName($col);
            $spreadsheet->getActiveSheet()
              ->setCellValue($colName.$row, $this->getValueByColumn($column));
            $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(30);
          }
          $row++;
        }

        if($this->adminConfiguration->get("download.xlsTheme.enabled"))
        {
          $styleHeaderArray = array(
            "font"    =>  array(
              "bold"    => true,
              "size"    => 10,
              "color"   => array('rgb' => u($this->adminConfiguration->get("download.xlsTheme.header.color"))->trim("#")->toString()),
              'name'    => 'Verdana'
            ),
            'fill' => array(
              'fillType' => Fill::FILL_SOLID,
              'startColor' => array(
                'rgb' => u($this->adminConfiguration->get("download.xlsTheme.header.background"))->trim("#")->toString(),
              )
            ),
          );

          $styleContentArray = array(
            "font"    =>  array(
              "bold"    => true,
              "size"    => 10,
              "color"   => array('rgb' => u($this->adminConfiguration->get("download.xlsTheme.content.color"))->trim("#")->toString()),
              'name'    => 'Verdana'
            ),
            'fill' => array(
              'fillType' => Fill::FILL_SOLID,
              'startColor' => array(
                'rgb' => u($this->adminConfiguration->get("download.xlsTheme.content.background"))->trim("#")->toString(),
              )
            ),
          );

          $spreadsheet->getActiveSheet()
            ->getStyle("A1:{$this->colName(count($section->headerColumns())-1)}1")
            ->applyFromArray($styleHeaderArray)
            ->getAlignment()
            ->setHorizontal('left')
            ->setVertical('center');

          $spreadsheet->getActiveSheet()
            ->getStyle("A1:{$this->colName(count($section->headerColumns())-1)}".($row-1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

          $spreadsheet->getActiveSheet()
            ->getStyle("A2:{$this->colName(count($section->headerColumns())-1)}".($row-1))
            ->applyFromArray($styleContentArray)
            ->getAlignment()
            ->setHorizontal('left')
            ->setVertical('center');
        }

        $sheet++;
      }
      $this->xlsxValues = new Xlsx($spreadsheet);
    }
    return $this;
  }

  /**
   * @return void
   * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
   */
  protected function xlsxPush(): void
  {
    $this->xlsxValues?->save('php://output');
  }

  /**
   * @param string $value
   * @param array<string, string> $parameters
   *
   * @return string
   */
  protected function translate(string $value, array $parameters = array()): string
  {
    if($this->listMapper)
    {
      $parameters = array_merge($this->listMapper->getTranslateParameters(), $parameters);
      return u($this->translator->trans($value, $parameters, $this->adminConfiguration->get('download.trans_domain', $this->listMapper->getTranslateDomain())))
        ->trim()
        ->toString();
    }
    return "";
  }


  /**
   * @param ColumnInterface<T> $column
   * @param bool $accepteArray
   *
   * @return string|array<int|string, mixed>
   */
  protected function getValueByColumn(ColumnInterface $column, bool $accepteArray = false): string|array
  {
    $columnValue = $column->getValue();
    if(!is_array($columnValue) && !is_object($columnValue))
    {
      if(is_bool($columnValue))
      {
        $columnValue = $columnValue ? "true" : "false";
        $columnValue = $this->translate("boolean_value.{$columnValue}");
      }
      elseif($column->withLanguage())
      {
        $columnValue = $this->translate($columnValue);
      }
      return u($columnValue)
        ->trim()
        ->toString();
    }
    else
    {
      if($columnValue instanceof EntityInterface)
      {
        $columnValue = array($columnValue);
      }

      $finalValue = array();
      if(is_array($columnValue))
      {
        foreach ($columnValue as $key => $value)
        {
          if($value instanceof EntityInterface)
          {
            if(method_exists($value, "arrayToDownload"))
            {
              $finalValue[] = $value->arrayToDownload();
            }
            else
            {
              $finalValue[] = $value->arrayObject();
            }
          }
          else
          {
            $finalValue[$key] = $value;
          }
        }
      }
      if($accepteArray)
      {
        return $finalValue;
      }
      else
      {
        return u($this->transformArrayToString($finalValue))
          ->trim()
          ->toString();
      }
    }
  }

  /**
   * @param array<int|string, mixed> $array
   * @param int $indent
   *
   * @return string
   */
  protected function transformArrayToString(array $array, int $indent = 0): string
  {
    $textFinal = "";
    foreach($array as $key => $value)
    {
      if(is_array($value))
      {
        $textFinal .= str_repeat(" ", $indent)."{$key} : \n".$this->transformArrayToString($value, $indent+4)."\n\n";
      }
      else
      {
        $textFinal .= str_repeat(" ", $indent)."{$key} : {$value}\n";
      }
    }
    return $textFinal;
  }

  /**
   * @param int $col
   *
   * @return string
   */
  protected function colName(int $col): string
  {
    $prefix = "";
    if($col > 25 )
    {
      $prefix = "A";
    }
    return $prefix.chr(($col%26)+65);
  }

}