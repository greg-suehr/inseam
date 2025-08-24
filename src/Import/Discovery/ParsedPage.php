<?php

namespace App\Import\Discovery;

final readonly class ParsedPage
{
  /**
   * @param AssetReference[] $assets
   * @param StylesheetReference[] $stylesheets  
   * @param ScriptReference[] $scripts
   * @param string[] $links
   */
  public function __construct(
    public string $url,
    public string $title,
    public string $html,
    public array $meta,
    public array $assets,
    public array $stylesheets,
    public array $scripts,
    public array $links
  ) {}
}
