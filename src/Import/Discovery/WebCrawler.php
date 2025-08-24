<?php

namespace App\Import\Discovery;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class WebCrawler {
  public function __construct(
    private HttpClientInterface $http,
    private HtmlParser $parser,
    private LoggerInterface $logger
  ) {}

  public function crawl(
    string $entryUrl,
    int $maxPages,
    int $maxDepth,
    bool $respectRobots = true,
    bool $followSubdomains = false
  ): CrawlResult {
      $result = new CrawlResult();
      $visited = [];
      $queue = [new CrawlQueueItem($entryUrl, 0)];
      $base = strtolower(parse_url($entryUrl, PHP_URL_HOST) ?? '');

      while (!empty($queue) && count($result->pages) < $maxPages) {
        $item = array_shift($queue);
        
        if (isset($visited[$this->normalizeUrlKey($item->url)]) || $item->depth > $maxDepth) {
          continue;
        }
        
        try {
          $this->logger->debug('Crawling page', ['url' => $item->url, 'depth' => $item->depth]);

          $html = $this->fetchPage($item->url);
          $parsedPage = $this->parser->parsePage($item->url, $html);
          
          $result->addPage($parsedPage, $item->depth);
          $visited[$this->normalizeUrlKey($item->url)] = true;

          foreach ($parsedPage->links as $link) {
            if (isset($visited[$this->normalizeUrlKey($link)])) continue;

            $kind = $this->classifyUrl($link);
            if ($kind !== 'maybe-page') continue;
            
            if ($this->shouldFollow($link, $base, $followSubdomains)) {
              $queue[] = new CrawlQueueItem($link, $item->depth + 1);
            }
          }
        } catch (\Exception $e) {
          $this->logger->warning('Failed to crawl page', [
            'url' => $item->url,
            'error' => $e->getMessage()
                ]);
          $result->addError($item->url, $e->getMessage());
        }
      }

      return $result;
    }

  private const ASSET_EXT = [
    'pdf','png','jpg','jpeg','webp','gif','svg',
    'css','js','ico','woff','woff2','ttf','otf','eot',
    'zip','tar','gz','mp3','mp4','webm','avi',
  ];
  
  private function classifyUrl(string $url): string # 'skip'|'asset'|'maybe-page'
  {
    $parts = parse_url($url);
    $scheme = strtolower($parts['scheme'] ?? '');
    if (!in_array($scheme, ['http','https'], true)) return 'skip';

    $path = $parts['path'] ?? '';
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext !== '' && in_array($ext, self::ASSET_EXT, true)) {
      return 'asset';
    }
    
    return 'maybe-page';
  }  

  private function fetchPage(string $url): string
  {
    $response = $this->http->request('GET', $url, [
      'timeout' => 30,
      'max_redirects' => 5,
      'headers' => [
        'User-Agent' => 'ImportBot/1.0',
        'Accept' => 'text/html,application/xhtml+xml',
      ]
    ]);
    
    if ($response->getStatusCode() !== 200) {
      throw new \RuntimeException("HTTP {$response->getStatusCode()} error for $url");
    }
    
    $contentType = $response->getHeaders()['content-type'][0] ?? '';
      if (!str_contains($contentType, 'text/html')) {
        throw new \RuntimeException("Non-HTML content type: $contentType");
      }
      
      return $response->getContent();
    }

  private function normalizeUrlKey(string $url): string
  {
    $p = parse_url($url);
    if ($p === false) return $url;
    
    $scheme = strtolower($p['scheme'] ?? 'http');
    $host   = strtolower($p['host'] ?? '');
    $port   = isset($p['port']) ? ':'.$p['port'] : '';
    $path   = $p['path'] ?? '/';
    $query  = isset($p['query']) ? '?'.$p['query'] : '';
    
    return sprintf('%s://%s%s%s%s', $scheme, $host, $port, $path, $query);
  }  
  
  private function shouldFollow(string $url, string $base, bool $followSubdomains): bool
  {
    $parts = parse_url($url);
    $scheme = strtolower($parts['scheme'] ?? '');
    $host   = strtolower($parts['host'] ?? '');

    if (!in_array($scheme, ['http','https'], true)) {
      return false; // mailto:, tel:, javascript:, data:, blob:, ftp:, etc.
    }
    
    if ($host === '') {
        return false;
    }
    
    if (!$followSubdomains) {
      return $host === $base;
    }
    
    return $host === $base || str_ends_with($host, '.' . $base);
    }
}

