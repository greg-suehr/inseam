<?php

namespace App\Import\Discovery;

use App\Import\Render\HeadlessRenderer;
use DOMDocument;
use DOMXPath;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use Psr\Log\LoggerInterface;

final class WebCrawler {
  public function __construct(
    private HttpClientInterface $http,
    private HtmlParser $parser,
    private LoggerInterface $logger,
    private ?HeadlessRenderer $renderer = null,
    private readonly bool $enableHeadlessBrowser = false
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
    $html = $this->httpFetch($url);
    
    if ($this->shouldHeadless($html, $url)) {
      if ($this->renderer === null) {
        $this->logger->warning('Headless requested but no renderer configured', ['url' => $url]);
        return $html;
      }
      $this->logger->debug('Falling back to headless render', ['url' => $url]);
      try {
        # DEBUG
        # echo "executing headless render\n";
        return $this->renderer->renderToHtml($url, 'main, [role="main"], article, #content, .content, .page-content');
      } catch (\Throwable $e) {
        $this->logger->error('Headless render failed; continuing with static HTML', [
          'url' => $url, 'error' => $e->getMessage()
                ]);
        return $html;
      }
    }
    
    return $html;
  }

  private function httpFetch(string $url): string
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

  /**
   * Is this a JS-rendered shell (SPA) with little/no SSR content
   */
  private function looksLikeJsShell(string $html, string $url): bool
  {
    $minLength = 200;
    if (strlen($html) < $minLength) {
      return true;
    }
    
    $lower = strtolower($html);
    $spaMarkers = [
      'id="__next"', 'id="root"', 'id="app"', 'data-reactroot', 'ng-version',
      'vite/client', 'webpackJsonp', 'window.__apollo', 'window.__INITIAL_STATE__',
      'squarespace'
    ];

    foreach ($spaMarkers as $marker) {
      if (str_contains($lower, $marker)) {
        if ($this->visibleCharRatio($lower) < 0.03) {
          return true;
        }
      }
    }

    // Lightweight DOM pass
    $doc = new DOMDocument();
    // @ - Suppress parser warnings on sloppy HTML
    @$doc->loadHTML($html);
    $xp = new DOMXPath($doc);

    // Count scripts and visible text density
    $scriptCount = $xp->query('//script')->length;
    $noscriptText = $this->stringLen($xp, '//noscript');
    $bodyText = $this->textLen($xp, '//body');
    
    // If body text is extremely small, or scripts dominate, it's likely a shell
    if ($bodyText < 400) {
      if ($scriptCount >= 5) {
        return true;
      }

      $phCount = $xp->query('//p | //h1 | //h2 | //h3')->length;
      if ($phCount === 0) {
        return true;
      }
    }

    // Squarespace: many pages SSR very little, but put real content inside <noscript>
    // If noscript has way more text than body, we didn’t get SSR content
    if ($noscriptText > 0 && ($noscriptText > 4 * max(1, $bodyText))) {
      return true;
    }
    
    // If the <main> / [role=main] exists but contains almost no text, it’s client-filled
    $mainLen = $this->textLen($xp, '//*[@role="main"] | //main | //article | //*[@id="content"]');
    if ($mainLen < 150 && $scriptCount >= 8) {
      return true;
    }
    
    // Ratio catch-all: scripts/length heavy and visible character ratio tiny
    $scriptRatio = $scriptCount / max(1, substr_count($lower, '<'));
    if ($scriptRatio > 0.20 && $this->visibleCharRatio($lower) < 0.02) {
      return true;
    }
    
    // Squarespace domain hint
    if (preg_match('~squarespace\.com|\.squarespace\.(?:com|site)~i', $url)) {
      if ($this->visibleCharRatio($lower) < 0.035) {
        return true;
      }
    }

    # TODO: fix
    return false;
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

  private function shouldHeadless(string $html, string $url): bool
  {
    if (!$this->enableHeadlessBrowser) {
      return false;
    }

    $res =  $this->looksLikeJsShell($html, $url);

    return $res;
  }

  private function stringLen(DOMXPath $xp, string $xpath): int
  {
    $nodes = $xp->query($xpath);
    $sum = 0;
    foreach ($nodes as $n) {
      $sum += strlen($xp->document->saveHTML($n) ?: '');
    }
    return $sum;
  }  
  
  private function textLen(DOMXPath $xp, string $xpath): int
  {
    $nodes = $xp->query($xpath);
    $sum = 0;
    foreach ($nodes as $n) {
      $sum += strlen(trim($n->textContent ?? ''));
    }
    return $sum;
  }
  
  /** Approximate “visible” character ratio ignoring tags and script-heavy pages */
  private function visibleCharRatio(string $lowerHtml): float
  {
    // Strip tags, shrink whitespace
    $textish = preg_replace('~<script\b[^<]*(?:(?!</script>)<[^<]*)*</script>~i', '', $lowerHtml);
    $textish = strip_tags($textish ?? '');
    $textish = preg_replace('~\s+~', ' ', $textish ?? '');
    $visible = strlen(trim($textish ?? ''));
    $total = strlen($lowerHtml);
    if ($total === 0) return 0.0;
    return $visible / $total;
  }
}

