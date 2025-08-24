<?php

namespace App\Import\Discovery;

use App\Import\DTO\DiscoveryResult;
use App\Import\DTO\ImportPolicy;
use App\Import\DTO\ImportSource;
use App\Import\DTO\ContentGraph\AssetKind;
use App\Import\DTO\ContentGraph\AssetNode;
use App\Import\DTO\ContentGraph\Edge;
use App\Import\DTO\ContentGraph\Node;
use App\Import\DTO\ContentGraph\ScriptNode;
use App\Import\DTO\ContentGraph\StylesheetNode;
use App\Import\Discovery\ContentGraphBuilder;
use App\Import\Discovery\HtmlParser;
use App\Import\Entity\ImportDiscovery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use Psr\Log\LoggerInterface;

final class DiscoveryEngine
{
  private const MAX_PAGES = 1000;
  private const MAX_DEPTH = 10;
  
  public function __construct(
    private HttpClientInterface $http,
    private HtmlParser $parser,
    private ContentGraphBuilder $graphBuilder,
    private EntityManagerInterface $em,
    private LoggerInterface $logger
  ) {}
  
  public function discover(ImportSource $source, ImportPolicy $policy): DiscoveryResult
  {
      $startTime = microtime(true);
      $this->logger->info('Starting discovery', [
        'entryUrl' => $source->entryUrlOrFile,
        'platform' => $source->platform,
        'maxPages' => $policy->maxPages
      ]);
      
      $crawler = new WebCrawler($this->http, $this->parser, $this->logger);
      $crawlResult = $crawler->crawl(
        $source->entryUrlOrFile, 
        min($policy->maxPages, self::MAX_PAGES),
        self::MAX_DEPTH,
        $policy->respectRobotsTxt,
        $policy->followSubdomains
      );
      
      $graph = $this->graphBuilder->buildGraph($crawlResult);
      
      $stats = $this->generateStats($crawlResult, microtime(true) - $startTime);
      
      $this->persistDiscovery($source, $graph, $stats);
      
      $this->logger->info('Discovery completed', [
        'graphId' => $graph->graphId,
        'nodeCount' => count($graph->nodes),
        'edgeCount' => count($graph->edges),
        'duration' => round(microtime(true) - $startTime, 2) . 's'
      ]);
      
      return $graph;
    }
  
  private function generateStats(CrawlResult $crawl, float $duration): array
  {
        $assetsByType = [];
        foreach ($crawl->getAllAssets() as $asset) {
          $assetsByType[$asset->kind->value] = ($assetsByType[$asset->kind->value] ?? 0) + 1;
        }
        
        return [
          'duration' => $duration,
          'pages_discovered' => count($crawl->pages),
          'unique_assets' => count($crawl->getAllAssets()),
          'stylesheets' => count($crawl->getAllStylesheets()),
          'scripts' => count($crawl->getAllScripts()),
          'assets_by_type' => $assetsByType,
          'total_size_bytes' => $crawl->getTotalSizeBytes(),
          'crawl_depth_reached' => $crawl->getMaxDepthReached(),
          'errors' => $crawl->getErrors()
        ];
    }
  
  private function persistDiscovery(ImportSource $source, DiscoveryResult $graph, array $stats): void
  {
        $graphJson = [
          'nodes' => array_map([$this, 'serializeNode'], $graph->nodes),
          'edges' => array_map([$this, 'serializeEdge'], $graph->edges)
        ];
        
        $discovery = new ImportDiscovery(
          sessionId: $source->siteId ? (int) $source->siteId : 0, // TODO: Get actual session ID
          graphId: $graph->graphId,
          graphJson: $graphJson,
          stats: $stats
        );
        
        $this->em->persist($discovery);
        $this->em->flush();
    }
  
  private function serializeNode(Node $node): array
  {
        $base = [
          'id' => $node->id,
          'sourceUrl' => $node->sourceUrl,
          'hash' => $node->hash,
          'type' => get_class($node)
        ];
        
        return match (get_class($node)) {
          PageNode::class => $base + [
            'title' => $node->title,
            'meta' => $node->meta,
            'rawHtml' => base64_encode($node->rawHtml)
          ],
          AssetNode::class => $base + [
            'kind' => $node->kind->value,
            'contentType' => $node->contentType,
            'sizeBytes' => $node->sizeBytes
          ],
          StylesheetNode::class => $base + [
            'cssText' => base64_encode($node->cssText)
          ],
          ScriptNode::class => $base + [
            'jsText' => base64_encode($node->jsText),
            'inline' => $node->inline
          ],
          default => $base
        };
    }
  
  private function serializeEdge(Edge $edge): array
  {
        return [
          'fromId' => $edge->fromId,
          'toId' => $edge->toId,
          'type' => $edge->type
        ];
    }
}
