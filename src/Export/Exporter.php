<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Export;

use Austral\AdminBundle\Event\AdminEventDispatcher;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Event\ExportAdminEvent;
use Austral\AdminBundle\Event\QueryAdminEvent;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Page\EntityQuery;
use Austral\AdminBundle\Page\ListConfigurator;
use Austral\AdminBundle\Page\ListPage;
use Austral\AdminBundle\Page\ListSection;
use Austral\FilterBundle\Handler\FilterHandler;
use Austral\ListBundle\Mapper\ListMapper;
use Austral\ListBundle\View\ListViewBuilder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Austral Exporter.
 *
 * Export of the list of an entity module (filters of the default group applied): the visible columns of the list,
 * changed by AdminEvents::EXPORT_CONFIGURE, in CSV, JSON or XLSX (phpoffice/phpspreadsheet).
 *
 * A text starting with =, +, -, @ or a tab is prefixed with a quote in CSV and written as a text in XLSX: a
 * spreadsheet does not run it as a formula.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class Exporter
{
  public const array FORMATS = ['csv', 'json', 'xlsx'];

  private const array CONTENT_TYPES = [
    'csv' => 'text/csv; charset=UTF-8',
    'json' => 'application/json',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  ];

  /**
   * @param ListConfigurator         $configurator
   * @param EntityQuery              $query
   * @param FilterHandler            $filterHandler
   * @param ListPage                 $listPage
   * @param ListViewBuilder          $listViewBuilder
   * @param AdminEventDispatcher     $dispatcher
   * @param TranslatorInterface|null $translator
   * @param string                   $csvDelimiter
   */
  public function __construct(
    private readonly ListConfigurator $configurator,
    private readonly EntityQuery $query,
    private readonly FilterHandler $filterHandler,
    private readonly ListPage $listPage,
    private readonly ListViewBuilder $listViewBuilder,
    private readonly AdminEventDispatcher $dispatcher,
    private readonly ?TranslatorInterface $translator = null,
    private readonly string $csvDelimiter = ',',
  ) {
  }

  /**
   * @param Module  $module
   * @param Request $request
   * @param string  $format
   *
   * @return StreamedResponse
   */
  public function export(Module $module, Request $request, string $format): StreamedResponse
  {
    if (!\in_array($format, $module->getDefinition()->getDownloadFormats(), true) || !\in_array($format, self::FORMATS, true))
    {
      throw new NotFoundHttpException(\sprintf('The format "%s" is not an export format of the admin module "%s".', $format, $module->getPath()));
    }
    $listEvent = $this->configurator->configureList($module, $request);
    $mapper = $listEvent->getMapper();
    $filename = implode('-', [str_replace('/', '-', $module->getPath()), ...$module->getVariantKeys(), date('Y-m-d')]);
    $event = $this->dispatcher->dispatch(new ExportAdminEvent($module, $request, $mapper, $format, $filename), AdminEvents::EXPORT_CONFIGURE);

    $section = new ListSection(ListSection::DEFAULT);
    $filterMapper = $this->configurator->configureFilters($module, $request, [$section]);
    $queryBuilder = $this->query->queryBuilder($module, $request, QueryAdminEvent::CONTEXT_EXPORT);
    $this->listPage->applyFilters($queryBuilder, $filterMapper, $this->filterHandler->handle($filterMapper, $request), $section);
    ListPage::defaultOrder($queryBuilder, $module);
    $headers = $this->headers($mapper);
    $rows = function () use ($queryBuilder, $mapper): \Generator {
      foreach ($queryBuilder->getQuery()->toIterable() as $item)
      {
        if (\is_object($item))
        {
          yield array_map(self::text(...), $this->listViewBuilder->values($mapper, $item));
        }
      }
    };

    $response = new StreamedResponse(match ($format)
    {
      'csv' => fn () => $this->csv($headers, $rows()),
      'json' => static fn () => self::json($headers, $rows()),
      default => static fn () => self::xlsx($headers, $rows()),
    });
    $response->headers->set('Content-Type', self::CONTENT_TYPES[$format]);
    $filename = "{$event->getFilename()}.{$format}";
    $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename, (string) preg_replace('/[^A-Za-z0-9._-]/', '-', $filename)));
    $response->headers->set('X-Content-Type-Options', 'nosniff');

    return $response;
  }

  /**
   * @param mixed $value
   *
   * @return string
   */
  public static function text(mixed $value): string
  {
    return match (true)
    {
      null === $value => '',
      \is_bool($value) => $value ? '1' : '0',
      \is_scalar($value), $value instanceof \Stringable => (string) $value,
      $value instanceof \BackedEnum => (string) $value->value,
      $value instanceof \UnitEnum => $value->name,
      $value instanceof \DateTimeInterface => $value->format(\DateTimeInterface::ATOM),
      is_iterable($value) => implode(', ', array_map(self::text(...), \is_array($value) ? $value : iterator_to_array($value, false))),
      default => '',
    };
  }

  /**
   * @param string $value
   *
   * @return string The value, a quote before a formula
   */
  public static function neutralize(string $value): string
  {
    return '' !== $value && \in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'{$value}" : $value;
  }

  /**
   * @param ListMapper $mapper
   *
   * @return array<string, string> Column name => label
   */
  private function headers(ListMapper $mapper): array
  {
    $headers = [];
    foreach ($mapper->getVisibleColumns() as $name => $column)
    {
      $label = $column->getOption('label');
      $parameters = $column->getOption('label_parameters');
      $headers[$name] = \is_string($label)
        ? ($this->translator?->trans($label, \is_array($parameters) ? $parameters : [], ListConfigurator::TRANSLATION_DOMAIN) ?? $label)
        : $name;
    }

    return $headers;
  }

  /**
   * @param array<string, string>           $headers
   * @param iterable<array<string, string>> $rows
   *
   * @return void
   */
  private function csv(array $headers, iterable $rows): void
  {
    $output = fopen('php://output', 'w');
    if (false === $output)
    {
      return;
    }
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, array_map(self::neutralize(...), array_values($headers)), $this->csvDelimiter, escape: '');
    foreach ($rows as $row)
    {
      fputcsv($output, array_map(self::neutralize(...), array_values($row)), $this->csvDelimiter, escape: '');
    }
    fclose($output);
  }

  /**
   * @param array<string, string>           $headers
   * @param iterable<array<string, string>> $rows
   *
   * @return void
   */
  private static function json(array $headers, iterable $rows): void
  {
    echo '[';
    $first = true;
    foreach ($rows as $row)
    {
      $line = [];
      foreach (array_keys($headers) as $name)
      {
        $line[$name] = $row[$name] ?? '';
      }
      echo ($first ? '' : ','), json_encode($line, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);
      $first = false;
    }
    echo ']';
  }

  /**
   * @param array<string, string>           $headers
   * @param iterable<array<string, string>> $rows
   *
   * @return void
   */
  private static function xlsx(array $headers, iterable $rows): void
  {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $line = 1;
    foreach ([array_values($headers), ...self::lines($headers, $rows)] as $values)
    {
      foreach ($values as $index => $value)
      {
        $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($index + 1).$line, $value, DataType::TYPE_STRING);
      }
      ++$line;
    }
    $sheet->getStyle('1:1')->getFont()->setBold(true);
    new Xlsx($spreadsheet)->save('php://output');
    $spreadsheet->disconnectWorksheets();
  }

  /**
   * @param array<string, string>           $headers
   * @param iterable<array<string, string>> $rows
   *
   * @return \Generator<int, list<string>>
   */
  private static function lines(array $headers, iterable $rows): \Generator
  {
    foreach ($rows as $row)
    {
      $line = [];
      foreach (array_keys($headers) as $name)
      {
        $line[] = $row[$name] ?? '';
      }
      yield $line;
    }
  }
}
