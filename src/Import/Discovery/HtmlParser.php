<?php

namespace App\Import\Discovery;

use App\Import\DTO\ContentGraph\AssetKind;
use App\Import\DTO\ContentGraph\AssetNode;
use App\Import\DTO\ContentGraph\Edge;
use App\Import\DTO\ContentGraph\Node;
use App\Import\DTO\ContentGraph\PageNode;
use App\Import\DTO\ContentGraph\ScriptNode;
use App\Import\DTO\ContentGraph\StylesheetNode;
use Psr\Log\LoggerInterface;

final class HtmlParser
{
  public function __construct(private LoggerInterface $logger) {}
  
  public function parsePage(string $url, string $html): ParsedPage
  {
      $doc = new \DOMDocument();
      $oldSetting = libxml_use_internal_errors(true);
      $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
      libxml_use_internal_errors($oldSetting);
      
      $xpath = new \DOMXPath($doc);
      
      $title = $this->extractTitle($xpath);
      $meta = $this->extractMeta($xpath);
      
      $assets = $this->extractAssets($xpath, $url);
      $stylesheets = $this->extractStylesheets($xpath, $url);
      $scripts = $this->extractScripts($xpath, $url);
      $links = $this->extractLinks($xpath, $url);
      
      $this->logger->debug('Parsed HTML page', [
        'url' => $url,
        'title' => $title,
        'assets' => count($assets),
        'stylesheets' => count($stylesheets),
        'scripts' => count($scripts),
        'links' => count($links)
        ]);
      
      return new ParsedPage($url, $title, $html, $meta, $assets, $stylesheets, $scripts, $links);
    }
  
  private function extractTitle(\DOMXPath $xpath): string
  {
      $titleNodes = $xpath->query('//title');
      return $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';
    }
  
  private function extractMeta(\DOMXPath $xpath): array
  {
      $meta = [];
        
      foreach ($xpath->query('//meta[@name]') as $node) {
        $name = $node->getAttribute('name');
        $content = $node->getAttribute('content');
        if ($name && $content) {
          $meta[$name] = $content;
        }
      }
      
      foreach ($xpath->query('//meta[@property]') as $node) {
        $property = $node->getAttribute('property');
        $content = $node->getAttribute('content');
        if ($property && $content) {
          $meta[$property] = $content;
        }
      }
      
      return $meta;
    }
  
  private function extractAssets(\DOMXPath $xpath, string $baseUrl): array
  {
      $assets = [];
        
      foreach ($xpath->query('//img[@src]') as $img) {
        $src = $img->getAttribute('src');
        if ($src) {
          $absoluteUrl = $this->resolveUrl($src, $baseUrl);
          $assets[] = new AssetReference($absoluteUrl, AssetKind::image, [
            'alt' => $img->getAttribute('alt'),
            'width' => $img->getAttribute('width') ?: null,
            'height' => $img->getAttribute('height') ?: null,
          ]);
        }
      }
      
      foreach ($xpath->query('//video[@src] | //source[@src]') as $video) {
        $src = $video->getAttribute('src');
        if ($src) {
          $absoluteUrl = $this->resolveUrl($src, $baseUrl);
          $assets[] = new AssetReference($absoluteUrl, AssetKind::video);
        }
      }
      
      foreach ($xpath->query('//link[@href]') as $link) {
        $rel = strtolower($link->getAttribute('rel'));
        $href = $link->getAttribute('href');
        
        if ($href && in_array($rel, ['preload', 'prefetch'])) {
          $as = strtolower($link->getAttribute('as'));
          if ($as === 'font') {
            $absoluteUrl = $this->resolveUrl($href, $baseUrl);
            $assets[] = new AssetReference($absoluteUrl, AssetKind::font);
          }
        }
      }
      
      return $assets;
    }
  
  private function extractStylesheets(\DOMXPath $xpath, string $baseUrl): array
  {
      $stylesheets = [];
        
      // External stylesheets
      foreach ($xpath->query('//link[@rel="stylesheet"][@href]') as $link) {
        $href = $link->getAttribute('href');
        if ($href) {
          $absoluteUrl = $this->resolveUrl($href, $baseUrl);
          $stylesheets[] = new StylesheetReference($absoluteUrl, false);
        }
      }
      
      // Inline styles  
      foreach ($xpath->query('//style') as $style) {
        $css = trim($style->textContent);
        if ($css) {
          $stylesheets[] = new StylesheetReference($baseUrl, true, $css);
        }
      }
      
      return $stylesheets;
    }
  
  private function extractScripts(\DOMXPath $xpath, string $baseUrl): array
  {
      $scripts = [];
      
      // External scripts
      foreach ($xpath->query('//script[@src]') as $script) {
        $src = $script->getAttribute('src');
        if ($src) {
          $absoluteUrl = $this->resolveUrl($src, $baseUrl);
          $scripts[] = new ScriptReference($absoluteUrl, false);
        }
      }
      
      // Inline scripts
      foreach ($xpath->query('//script[not(@src)]') as $script) {
        $js = trim($script->textContent);
        if ($js) {
          $scripts[] = new ScriptReference($baseUrl, true, $js);
        }
      }
      
      return $scripts;
    }
  
  private function extractLinks(\DOMXPath $xpath, string $baseUrl): array
  {
      $links = [];
        
      foreach ($xpath->query('//a[@href]') as $anchor) {
        $href = $anchor->getAttribute('href');
        if ($href && !str_starts_with($href, '#') && !str_starts_with($href, 'javascript:')) {
          $absoluteUrl = $this->resolveUrl($href, $baseUrl);
          $links[] = $absoluteUrl;
        }
      }
      
      return array_unique($links);
    }
  
  private function resolveUrl(string $url, string $baseUrl): string
  {
      if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        return $url;
      }
      
      if (str_starts_with($url, '//')) {
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
        return $scheme . ':' . $url;
      }
      
      if (str_starts_with($url, '/')) {
        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        return $scheme . '://' . $host . $port . $url;
      }
      
      $basePath = dirname(parse_url($baseUrl, PHP_URL_PATH) ?: '/');
      if ($basePath === '.') $basePath = '/';
      
      $parsed = parse_url($baseUrl);
      $scheme = $parsed['scheme'] ?? 'https';
      $host = $parsed['host'] ?? '';
      $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
      
      return $scheme . '://' . $host . $port . rtrim($basePath, '/') . '/' . ltrim($url, '/');
    }
}
