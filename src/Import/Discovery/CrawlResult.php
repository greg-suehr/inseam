<?php

namespace App\Import\Discovery;

final class CrawlResult
{
  /** @var ParsedPage[] */
  public array $pages = [];
  
  /** @var array<string, int> URL -> depth */
  private array $pageDepths = [];
  
  /** @var array<string, string> URL -> error message */
  private array $errors = [];
  
  public function addPage(ParsedPage $page, int $depth): void
  {
      $this->pages[] = $page;
      $this->pageDepths[$page->url] = $depth;
    }
  
  public function addError(string $url, string $error): void
  {
      $this->errors[$url] = $error;
    }
  
  /**
   * @return AssetReference[]
   */
  public function getAllAssets(): array
  {
      $allAssets = [];
      $seen = [];
      
      foreach ($this->pages as $page) {
        foreach ($page->assets as $asset) {
          if (!isset($seen[$asset->url])) {
            $allAssets[] = $asset;
            $seen[$asset->url] = true;
          }
        }
      }
      
      return $allAssets;
    }

  /**
   * @return StylesheetReference[]
   */
  public function getAllStylesheets(): array
  {
      $allStylesheets = [];
      $seen = [];
      
      foreach ($this->pages as $page) {
        foreach ($page->stylesheets as $stylesheet) {
          if (!isset($seen[$stylesheet->url])) {
            $allStylesheets[] = $stylesheet;
            $seen[$stylesheet->url] = true;
          }
        }
      }
      
      return $allStylesheets;
    }
  
  /**
   * @return ScriptReference[]
   */
  public function getAllScripts(): array
  {
      $allScripts = [];
      $seen = [];
      
      foreach ($this->pages as $page) {
        foreach ($page->scripts as $script) {
          if (!isset($seen[$script->url])) {
            $allScripts[] = $script;
            $seen[$script->url] = true;
          }
        }
      }
      
      return $allScripts;
    }
  
  public function getTotalSizeBytes(): int
  {
      return array_sum(array_map(fn($asset) => $asset->sizeBytes ?? 0, $this->getAllAssets()));
    }
  
  public function getMaxDepthReached(): int
  {
      return empty($this->pageDepths) ? 0 : max($this->pageDepths);
    }
  
  public function getErrors(): array
  {
      return $this->errors;
    }
  
  public function getPageDepth(string $url): ?int
  {
      return $this->pageDepths[$url] ?? null;
    }
}
