<?php

namespace App\Import\Discovery;

use App\Import\DTO\DiscoveryResult;
use App\Import\DTO\ContentGraph\AssetKind;
use App\Import\DTO\ContentGraph\AssetNode;
use App\Import\DTO\ContentGraph\Edge;
use App\Import\DTO\ContentGraph\PageNode;
use App\Import\DTO\ContentGraph\ScriptNode;
use App\Import\DTO\ContentGraph\StylesheetNode;
use Psr\Log\LoggerInterface;

final class ContentGraphBuilder
{
  public function __construct(private LoggerInterface $logger) {}
  
  public function buildGraph(CrawlResult $crawlResult): DiscoveryResult
  {
      $this->logger->info('Building content graph', [
        'pageCount' => count($crawlResult->pages),
        'assetCount' => count($crawlResult->getAllAssets())
        ]);
      
      $nodes = [];
      $edges = [];
      $nodeIdMap = [];
      
      // Build page nodes
      foreach ($crawlResult->pages as $pageData) {
        $pageNode = $this->createPageNode($pageData);
        $nodes[] = $pageNode;
        $nodeIdMap[$pageData->url] = $pageNode->id;
      }
      
      // Build asset nodes from all discovered assets
      $assetNodes = $this->createAssetNodes($crawlResult->getAllAssets());
      foreach ($assetNodes as $assetNode) {
        $nodes[] = $assetNode;
        $nodeIdMap[$assetNode->sourceUrl] = $assetNode->id;
      }
      
      // Build stylesheet nodes
      $stylesheetNodes = $this->createStylesheetNodes($crawlResult->getAllStylesheets());
      foreach ($stylesheetNodes as $stylesheetNode) {
        $nodes[] = $stylesheetNode;
        $nodeIdMap[$stylesheetNode->sourceUrl] = $stylesheetNode->id;
      }
      
      // Build script nodes
      $scriptNodes = $this->createScriptNodes($crawlResult->getAllScripts());
      foreach ($scriptNodes as $scriptNode) {
        $nodes[] = $scriptNode;
        $nodeIdMap[$scriptNode->sourceUrl] = $scriptNode->id;
      }
      
      // Build edges - relationships between nodes
      $edges = $this->createEdges($crawlResult, $nodeIdMap);
      
      $graphId = $this->generateGraphId($crawlResult);
      
      $this->logger->info('Content graph built', [
        'graphId' => $graphId,
        'nodeCount' => count($nodes),
        'edgeCount' => count($edges)
        ]);
      
      return new DiscoveryResult($graphId, $nodes, $edges);
    }
  
  private function createPageNode(ParsedPage $pageData): PageNode
  {
   if (strlen($pageData->html) > 5 * 1024 * 1024) { // 5MB limit
     throw new \RuntimeException('Page content too large');
   }
   
   return new PageNode(
     id: $this->generateNodeId('page', $pageData->url),
     sourceUrl: $pageData->url,
     hash: hash('sha256', $pageData->html),
     title: $pageData->title,
     meta: $pageData->meta,
     rawHtml: $pageData->html
   );
    }
  
  private function createAssetNodes(array $assets): array
  {
    $nodes = [];
    $seen = [];
    
    foreach ($assets as $asset) {
      if (isset($seen[$asset->url])) {
        continue;
      }
      $seen[$asset->url] = true;
      
      $nodes[] = new AssetNode(
        id: $this->generateNodeId('asset', $asset->url),
        sourceUrl: $asset->url,
        hash: hash('sha256', $asset->url), // Placeholder until we fetch content
        kind: $asset->kind,
        contentType: $this->guessContentType($asset->kind, $asset->url),
        sizeBytes: 0
      );
    }
    
    return $nodes;
    }
  
  private function createStylesheetNodes(array $stylesheets): array
  {
    $nodes = [];
    $seen = [];
    
    foreach ($stylesheets as $stylesheet) {
      if (isset($seen[$stylesheet->url])) {
        continue;
      }
      $seen[$stylesheet->url] = true;
      
      $cssText = $stylesheet->content ?? '';
      
      $nodes[] = new StylesheetNode(
        id: $this->generateNodeId('stylesheet', $stylesheet->url),
        sourceUrl: $stylesheet->url,
        hash: hash('sha256', $cssText),
        cssText: $cssText
      );
    }
    
    return $nodes;
    }
  
  private function createScriptNodes(array $scripts): array
  {
    $nodes = [];
    $seen = [];
    
    foreach ($scripts as $script) {
      if (isset($seen[$script->url])) {
        continue;
      }
      $seen[$script->url] = true;
      
      $jsText = $script->content ?? '';
      
      $nodes[] = new ScriptNode(
        id: $this->generateNodeId('script', $script->url),
        sourceUrl: $script->url,
        hash: hash('sha256', $jsText),
        jsText: $jsText,
        inline: $script->inline
      );
    }
    
    return $nodes;
    }
  
  private function createEdges(CrawlResult $crawlResult, array $nodeIdMap): array
  {
    $edges = [];
      
    foreach ($crawlResult->pages as $pageData) {
      $fromId = $nodeIdMap[$pageData->url] ?? null;
      if (!$fromId) continue;
      
      foreach ($pageData->links as $linkUrl) {
        $toId = $nodeIdMap[$linkUrl] ?? null;
        if ($toId) {
          $edges[] = new Edge($fromId, $toId, 'links_to');
        }
      }
      
      foreach ($pageData->assets as $asset) {
        $toId = $nodeIdMap[$asset->url] ?? null;
        if ($toId) {
          $edges[] = new Edge($fromId, $toId, 'references');
        }
      }
      
      foreach ($pageData->stylesheets as $stylesheet) {
        $toId = $nodeIdMap[$stylesheet->url] ?? null;
        if ($toId) {
          $edges[] = new Edge($fromId, $toId, 'includes');
        }
      }
      
      foreach ($pageData->scripts as $script) {
        $toId = $nodeIdMap[$script->url] ?? null;
        if ($toId) {
          $edges[] = new Edge($fromId, $toId, 'executes');
        }
      }
    }
    
    return $edges;
    }
  
  private function generateNodeId(string $type, string $url): string
  {
    return $type . '_' . hash('sha256', $url);
    }
  
  private function generateGraphId(CrawlResult $crawlResult): string
  {
    $urls = array_map(fn($page) => $page->url, $crawlResult->pages);
    sort($urls);
    return 'graph_' . hash('sha256', implode('|', $urls));
    }
  
  private function guessContentType(AssetKind $kind, string $url): string
  {
    $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    
    return match ($kind) {
      AssetKind::image => match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'image/*'
      },
      AssetKind::video => match ($extension) {
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        default => 'video/*'
      },
      AssetKind::audio => match ($extension) {
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        default => 'audio/*'
      },
      AssetKind::font => match ($extension) {
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        default => 'font/*'
      },
      AssetKind::document => match ($extension) {
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        default => 'application/octet-stream'
      }
    };
  }
}
