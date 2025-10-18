<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;

/**
 * Loads *.json "template data packs" from a directory, normalizes fields,
 * validates structure, and produces a structured report.
 *
 * Every template result returns:
 *  - id: string (filename without .json)
 *  - ok: bool
 *  - template: array|null  (normalized data when ok=true, else null)
 *  - warnings: string[]
 *  - errors: string[]
 */
final class SiteTemplateLoader
{
  public function __construct(
    private string $templateDir = __DIR__ . '/../../public/templates'
  ) {}

  /**
   * Strict single-template load that throws on file-not-found and JSON errors.
   */
  public function load(string $templateName): array
  {
    $filePath = "{$this->templateDir}/{$templateName}.json";
    if (!is_file($filePath)) {
      throw new \RuntimeException("Template not found: $templateName");
    }
    
    $raw = file_get_contents($filePath);
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    
    [$ok, $normalized, $warnings, $errors] = $this->validateTemplate($data, $templateName);
    if (!$ok) {
      $message = "Template '{$templateName}' failed validation:\n - " . implode("\n - ", $errors);
      throw new \RuntimeException($message);
    }
    
    return $normalized;
  }

  /**
   * Soft load one template by id from the consolidated list. Returns normalized array or null.
   */
  public function find(string $id): ?array
  {
    foreach ($this->allWithReport()['templates'] as $tpl) {
      if ($tpl['id'] === $id && $tpl['ok']) {
        return $tpl['template'];
      }
    }
    return null;
  }
  
  /**
   * Load everything in the templates directory and return a list of valid temlates
   * along with a structured report of any erroring templates.
   *
   * @return array{
   *   templates: list<array{id:string, ok:bool, template:?array, warnings:array<string>, errors:array<string>}>,
   *   stats: array{total:int, valid:int, invalid:int}
   * }
   */
  public function allWithReport(): array
  {
    $results = [];
    $finder  = (new Finder())->files()->in($this->templateDir)->name('*.json');
    
    foreach ($finder as $file) {
      $id      = pathinfo($file->getFilename(), PATHINFO_FILENAME);
      $errors  = [];
      $warnings = [];
      $normalized = null;
      $ok = false;
      
      $raw = $file->getContents();
      try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
      } catch (\Throwable $e) {
        $results[] = [
          'id'       => $id,
          'ok'       => false,
          'template' => null,
          'warnings' => [],
          'errors'   => ["Invalid JSON: " . $e->getMessage()],
        ];
        continue;
      }

      try {
        [$ok, $normalized, $warnings, $errors] = $this->validateTemplate($data, $id);
      } catch (\Throwable $e) {
        $errors[] = "Validator exception: " . $e->getMessage();
        $ok = false;
        $normalized = null;
      }
      
      $results[] = [
        'id'       => $id,
        'ok'       => $ok,
        'template' => $ok ? $normalized : null,
        'warnings' => $warnings,
        'errors'   => $errors,
      ];
    }
    
    $valid   = \count(array_filter($results, fn($r) => $r['ok']));
    $invalid = \count($results) - $valid;
    
    return [
      'templates' => $results,
      'stats'     => [
        'total'   => \count($results),
        'valid'   => $valid,
        'invalid' => $invalid,
      ],
    ];
    }
  
  /**
   * Returns only the "safe summary" of each template (even if invalid).
   */
  public function all(): array
  {
    $report = $this->allWithReport();

    $out = [];
    foreach ($report['templates'] as $item) {
      $tpl = $item['template'] ?? [];
      
      $out[] = [
        'id'            => $item['id'],
        'name'          => $tpl['name'] ?? '(Unnamed)',
        'description'   => $tpl['description'] ?? '',
        'preview_image' => $tpl['preview_image'] ?? null,
        'pages'         => $tpl['pages'] ?? [],
        'assets'        => $tpl['assets'] ?? [],
        'styles'        => $tpl['styles'] ?? [],
        '_ok'           => $item['ok'],
        '_warnings'     => $item['warnings'],
        '_errors'       => $item['errors'],
      ];
    }
    
    return $out;
  }

  /**
   * Template schema validator and normalizer.
   *
   * Normalizes:
   *  - "blockTree" -> "blocktree" (tolerates camelCase legacy)
   *  - ensures 'assets', 'styles', 'pages' arrays exist
   *  - ensures each page has {slug,title,blocktree:[]}
   *  - trims strings and removes empty pages by default (warns instead of hard fail)
   *
   * Returns: [ok(bool), normalized(array), warnings(string[]), errors(string[])]
   */
  private function validateTemplate(array $data, string $id): array
  {
    $errors = [];
    $warnings = [];
    
    $normalized = [
      'id'            => $id, // system template ids are derived from filenames
      'name'          => $this->asString($data['name'] ?? null, '(Unnamed)', $warnings, 'name'),
      'description'   => $this->asString($data['description'] ?? null, '', $warnings, 'description'),
      'preview_image' => $this->nullableString($data['preview_image'] ?? null, $warnings, 'preview_image'),
      'styles'        => \is_array($data['styles'] ?? null) ? array_values($data['styles']) : [],
      'assets'        => \is_array($data['assets'] ?? null) ? array_values($data['assets']) : [],
      'params'        => \is_array($data['params'] ?? null) ? $data['params'] : [],
    ];

    if (!isset($data['pages'])) {
      $errors[] = "Missing required key: pages ([])";
      $normalized['pages'] = [];
      return [false, $normalized, $warnings, $errors];
    }

    if (!\is_array($data['pages'])) {
      $errors[] = "Key 'pages' must be an array.";
      $normalized['pages'] = [];
      return [false, $normalized, $warnings, $errors];
    }
    
    $pagesOut = [];
    foreach ($data['pages'] as $idx => $page) {
      if (!\is_array($page)) {
        $warnings[] = "Page #$idx is not an object; skipped.";
        continue;
      }
      
      $slug  = $this->requireString($page['slug'] ?? null, "pages[$idx].slug", $errors);
      $title = $this->requireString($page['title'] ?? null, "pages[$idx].title", $errors);
      
      $blocktreeRaw = $page['blocktree'] ?? ($page['blockTree'] ?? []);
      if (!\is_array($blocktreeRaw)) {
        $errors[] = "pages[$idx].blocktree must be an array (or blockTree).";
        $blocktree = [];
      } else {
        $blocktree = $blocktreeRaw;
      }
      
      $pageData = $page['data'] ?? [];
      if ($pageData !== [] && !\is_array($pageData)) {
        $warnings[] = "pages[$idx].data should be an object; coerced to empty object.";
        $pageData = [];
      }
      
      $btErrors = [];
      $btWarnings = [];
      $blocktree = $this->validateBlocks($blocktree, "pages[$idx].blocktree", $btWarnings, $btErrors);
      array_push($warnings, ...$btWarnings);
      array_push($errors, ...$btErrors);
      
      if ($slug && $title) {
        $pagesOut[] = [
          'slug'      => $slug,
          'title'     => $title,
          'blocktree' => $blocktree,
          'data'      => $pageData,
        ];
      }
    }

    $seen = [];
    $deduped = [];
    foreach ($pagesOut as $p) {
      if (isset($seen[$p['slug']])) {
        $warnings[] = "Duplicate page slug '{$p['slug']}'—later entry overrides earlier.";
      }
      $seen[$p['slug']] = true;
      $deduped[$p['slug']] = $p;
    }
    
    $normalized['pages'] = array_values($deduped);
    
    $assetsOut = [];
    foreach ($normalized['assets'] as $i => $asset) {
      if (!\is_array($asset) || !\is_string($asset['path'] ?? null) || $asset['path'] === '') {
        $warnings[] = "assets[$i] must be an object with non-empty 'path'; entry skipped.";
        continue;
      }
      $assetsOut[] = [
        'path' => trim((string) $asset['path']),
        'alt'  => isset($asset['alt']) && \is_string($asset['alt']) ? trim($asset['alt']) : null,
      ];
    }
    $normalized['assets'] = $assetsOut;
    
    $ok = empty($errors);
    
    return [$ok, $normalized, $warnings, $errors];
  }
  
  /**
   * Small block schema that checks each block has a 'type' and optional 'children' (array).
   */
  private function validateBlocks(array $blocks, string $ctx, array &$warnings, array &$errors): array
  {
    $out = [];
    foreach ($blocks as $i => $b) {
      if (!\is_array($b)) {
        $warnings[] = "$ctx[$i] is not an object; skipped.";
        continue;
      }
      
      $type = $b['type'] ?? null;
      if (!\is_string($type) || $type === '') {
        $errors[] = "$ctx[$i].type is required and must be a non-empty string.";
        // still push a block to keep positions stable? skips now
        continue;
      }
      
      if (isset($b['children']) && !\is_array($b['children'])) {
        $warnings[] = "$ctx[$i].children must be an array; coerced to [].";
        $b['children'] = [];
      }
      
      // Recurse into children if present
      if (!empty($b['children'])) {
        $b['children'] = $this->validateBlocks($b['children'], "$ctx[$i].children", $warnings, $errors);
      }
      
      $out[] = $b;
    }
    return $out;
  }
  
  private function asString(mixed $v, string $default, array &$warnings, string $key): string
  {
    if ($v === null) return $default;
    if (!\is_string($v)) {
      $warnings[] = "Key '$key' should be a string; coerced.";
      return (string) $v;
    }
    return trim($v);
  }
  
  private function nullableString(mixed $v, array &$warnings, string $key): ?string
  {
    if ($v === null || $v === '') return null;
    if (!\is_string($v)) {
      $warnings[] = "Key '$key' should be a string or null; coerced.";
      return (string) $v;
    }
    return trim($v);
  }

  private function requireString(mixed $v, string $path, array &$errors): ?string
  {
    if (!\is_string($v) || trim($v) === '') {
      $errors[] = "Missing or empty string: $path";
      return null;
    }
    return trim($v);
  }
  
  /**
   * Merge multiple template packs by id. Later IDs override earlier on slug collisions.
   * Useful if you ship a "base" pack plus a brand-themed override pack.
   */
  public function mergeTemplates(array $ids): array
  {
    $report = $this->allWithReport();
    $byId   = [];
    foreach ($report['templates'] as $t) {
      $byId[$t['id']] = $t;
    }
    
    $errors = [];
    $warnings = [];
    $merged = [
      'id'            => implode('+', $ids),
      'name'          => 'Merged Template',
      'description'   => '',
      'preview_image' => null,
      'pages'         => [],
      'assets'        => [],
      'styles'        => [],
      'params'        => [],
    ];
    
    foreach ($ids as $id) {
      if (!isset($byId[$id]) || !$byId[$id]['ok']) {
        $errors[] = "Cannot merge: template '$id' missing or invalid.";
        continue;
      }
      $tpl = $byId[$id]['template'];
      
      foreach ($tpl['styles'] as $style) {
        if (!\in_array($style, $merged['styles'], true)) {
          $merged['styles'][] = $style;
        }
      }
      
      $seenPaths = array_column($merged['assets'], 'path');
      foreach ($tpl['assets'] as $a) {
        if (!\in_array($a['path'], $seenPaths, true)) {
          $merged['assets'][] = $a;
          $seenPaths[] = $a['path'];
        }
      }
      
      $merged['params'] = array_merge($merged['params'], $tpl['params'] ?? []);
      
      $bySlug = [];
      foreach ($merged['pages'] as $p) $bySlug[$p['slug']] = $p;
      foreach ($tpl['pages'] as $p)  $bySlug[$p['slug']] = $p;
      $merged['pages'] = array_values($bySlug);
    }
    
    return [
      'ok'       => empty($errors),
      'template' => $merged,
      'warnings' => $warnings,
      'errors'   => $errors,
    ];
  }
}
