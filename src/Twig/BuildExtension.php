<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Austral Admin Build Twig Extension.
 *
 * Tags of the build of the administration, made by the project with Webpack Encore (encore.mjs) and read from its
 * entrypoints.json (option austral_admin.build):
 * - austral_admin_styles('austral-admin'): the <link> tags of an entry;
 * - austral_admin_scripts('austral-admin'): the <script> tags of an entry (deferred by default).
 * A file shared by two entries is rendered once per page. The URLs are prefixed with the base path of the request
 * (application in a sub-folder); the files are already versioned by the build.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class BuildExtension extends AbstractExtension implements ResetInterface
{
  /**
   * Entry of the administration, declared by configureAustralAdmin() (encore.mjs).
   */
  public const string ENTRY = 'austral-admin';

  /**
   * @var array<string, array{js?: list<string>, css?: list<string>}>|null Entries of entrypoints.json, read once
   */
  private ?array $entrypoints = null;

  /**
   * @var array<string, true> Files already rendered in the page
   */
  private array $rendered = [];

  /**
   * __construct
   *
   * @param string            $entrypointsPath entrypoints.json of the build of the project
   * @param RequestStack|null $requestStack
   */
  public function __construct(
    private readonly string $entrypointsPath,
    private readonly ?RequestStack $requestStack = null,
  ) {
  }

  /**
   * {@inheritDoc}
   */
  public function getFunctions(): array
  {
    return [
      new TwigFunction('austral_admin_styles', $this->styles(...), ['is_safe' => ['html']]),
      new TwigFunction('austral_admin_scripts', $this->scripts(...), ['is_safe' => ['html']]),
    ];
  }

  /**
   * styles
   * The <link> tags of the CSS files of an entry not rendered yet in the page.
   *
   * @param string                          $entry
   * @param array<string, string|bool|null> $attributes
   *
   * @return string
   *
   * @throws \InvalidArgumentException when the entry is not in the build
   * @throws \RuntimeException         when the build is missing or is not an entrypoints file
   * @throws \JsonException            when entrypoints.json is not valid JSON
   */
  public function styles(string $entry = self::ENTRY, array $attributes = []): string
  {
    $tags = [];
    foreach ($this->files($entry, 'css') as $file)
    {
      $tags[] = \sprintf('<link%s>', $this->attributes(['rel' => 'stylesheet', 'href' => $this->url($file)] + $attributes));
    }

    return implode("\n", $tags);
  }

  /**
   * scripts
   * The <script> tags of the JS files of an entry not rendered yet in the page, deferred by default.
   *
   * @param string                          $entry
   * @param array<string, string|bool|null> $attributes
   *
   * @return string
   *
   * @throws \InvalidArgumentException when the entry is not in the build
   * @throws \RuntimeException         when the build is missing or is not an entrypoints file
   * @throws \JsonException            when entrypoints.json is not valid JSON
   */
  public function scripts(string $entry = self::ENTRY, array $attributes = ['defer' => true]): string
  {
    $tags = [];
    foreach ($this->files($entry, 'js') as $file)
    {
      $tags[] = \sprintf('<script%s></script>', $this->attributes(['src' => $this->url($file)] + $attributes));
    }

    return implode("\n", $tags);
  }

  /**
   * reset
   * Forgets the files already rendered (between two requests of a long-running process).
   *
   * @return void
   */
  public function reset(): void
  {
    $this->rendered = [];
  }

  /**
   * files
   * The files of a type (css, js) of an entry, not rendered yet.
   *
   * @param string $entry
   * @param string $type
   *
   * @return list<string>
   *
   * @throws \InvalidArgumentException when the entry is not in the build
   * @throws \RuntimeException         when the build is missing or is not an entrypoints file
   * @throws \JsonException            when entrypoints.json is not valid JSON
   */
  private function files(string $entry, string $type): array
  {
    $entrypoints = $this->entrypoints();
    if (!\array_key_exists($entry, $entrypoints))
    {
      throw new \InvalidArgumentException(\sprintf('The entry "%s" is not in the build of the administration (%s). Available entries: "%s".', $entry, $this->entrypointsPath, implode('", "', array_keys($entrypoints))));
    }

    $files = [];
    foreach ($entrypoints[$entry][$type] ?? [] as $file)
    {
      if (!isset($this->rendered[$file]))
      {
        $this->rendered[$file] = true;
        $files[] = $file;
      }
    }

    return $files;
  }

  /**
   * entrypoints
   * The entries of entrypoints.json, read once.
   *
   * @return array<string, array{js?: list<string>, css?: list<string>}>
   *
   * @throws \RuntimeException when the build is missing or is not an entrypoints file
   * @throws \JsonException    when entrypoints.json is not valid JSON
   */
  private function entrypoints(): array
  {
    if (null === $this->entrypoints)
    {
      if (!is_file($this->entrypointsPath))
      {
        throw new \RuntimeException(\sprintf('The build of the administration is missing: "%s" does not exist. Build it in the project ("npm install" then "npm run build"), or set the option austral_admin.build.', $this->entrypointsPath));
      }
      $content = json_decode((string) file_get_contents($this->entrypointsPath), true, 512, \JSON_THROW_ON_ERROR);
      if (!\is_array($content) || !\is_array($content['entrypoints'] ?? null))
      {
        throw new \RuntimeException(\sprintf('"%s" is not an entrypoints file of Webpack Encore.', $this->entrypointsPath));
      }
      /** @var array<string, array{js?: list<string>, css?: list<string>}> $entries */
      $entries = $content['entrypoints'];
      $this->entrypoints = $entries;
    }

    return $this->entrypoints;
  }

  /**
   * url
   * The URL of a file of the build, prefixed with the base path of the request.
   *
   * @param string $file
   *
   * @return string
   */
  private function url(string $file): string
  {
    return ($this->requestStack?->getMainRequest()?->getBasePath() ?? '').$file;
  }

  /**
   * attributes
   * The escaped attributes of a tag: true renders the name alone, false and null are skipped.
   *
   * @param array<string, string|bool|null> $attributes
   *
   * @return string
   */
  private function attributes(array $attributes): string
  {
    $html = '';
    foreach ($attributes as $name => $value)
    {
      if (null === $value || false === $value)
      {
        continue;
      }
      $html .= true === $value
        ? ' '.htmlspecialchars($name, \ENT_QUOTES)
        : \sprintf(' %s="%s"', htmlspecialchars($name, \ENT_QUOTES), htmlspecialchars($value, \ENT_QUOTES));
    }

    return $html;
  }
}
