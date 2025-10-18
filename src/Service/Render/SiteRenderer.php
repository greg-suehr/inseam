<?php

namespace App\Service\Render;

use App\Entity\Site;
use App\Entity\Page;
use App\Service\Asset\AssetUrlResolver;
use App\Service\Render\BlockRenderer;

/**
 * SiteRenderer consumes a Site entity and generates a complete RenderArtifact
 * containing all HTML, assets, and metadata needed to deploy or serve the site.
 */
final class SiteRenderer
{
  public function __construct(
    private BlockRenderer $blockRenderer,
    private AssetUrlResolver $assetResolver,
  ) {}
  
  /**
   * Render an entire site to a deployment-ready artifact.
   */
  public function render(Site $site): RenderArtifact
  {
    $buildId = $this->generateBuildId();
    $pages = [];
    $cssRegistry = [];
    $jsRegistry = [];
    $imageRegistry = [];
    
    foreach ($site->getPages() as $page) {
      if (!$page->getIsPublished()) {
        continue;
      }
      
      $renderedPage = $this->renderPage($page, $site);
      
      $pages[] = [
        'path' => '/' . ltrim($page->getSlug(), '/'),
        'html' => $renderedPage['html'],
        'hashes' => $renderedPage['hashes'],
      ];
      
      // Collect unique CSS/JS/images from this page
      $this->collectAssets($renderedPage, $cssRegistry, $jsRegistry, $imageRegistry);
    }
    
    // Generate site-wide files
    $sitemap = $this->generateSitemap($site, $pages);
    $robots = $this->generateRobots($site);
    
    // Build manifest
    $manifest = [
      'siteId' => (string) $site->getId(),
      'buildId' => $buildId,
      'createdAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
      'pages' => array_map(function($page) {
                return [
                  'path' => $page['path'],
                  'etag' => md5($page['html']),
                  'size' => strlen($page['html']),
                ];
            }, $pages),
    ];
    
    return new RenderArtifact(
      pages: $pages,
      css: array_values($cssRegistry),
      js: array_values($jsRegistry),
      images: array_values($imageRegistry),
      sitemap: $sitemap,
      robots: $robots,
      manifest: $manifest,
      redirects: null,
      headers: null,
    );
  }
    
  /**
   * Render a single page with its blocktree.
   */
  public function renderPage(Page $page, Site $site): array
  {
    $data = $page->getData();
    $blocktree = $data['blocktree'] ?? [];
    
    $bodyHtml = '';
    $cssHashes = [];
    $jsHashes = [];
    
    foreach ($blocktree as $block) {
      $bodyHtml .= $this->blockRenderer->render($block, $site);
      
      // Track block-specific assets
      if (isset($block['_css'])) {
        $cssHashes = array_merge($cssHashes, (array) $block['_css']);
      }
      if (isset($block['_js'])) {
        $jsHashes = array_merge($jsHashes, (array) $block['_js']);
      }
    }
    
    // Wrap in full HTML document
    $seoData = $data['seo'] ?? [];
    $html = $this->wrapInDocument(
      body: $bodyHtml,
      title: $page->getTitle(),
      meta: $seoData,
      site: $site,
      cssHashes: array_unique($cssHashes),
      jsHashes: array_unique($jsHashes),
    );
    
    return [
      'html' => $html,
      'hashes' => [
        'css' => array_unique($cssHashes),
        'js' => array_unique($jsHashes),
      ],
    ];
    }
  
  /**
   * Wrap page content in a complete HTML5 document.
   */
  private function wrapInDocument(
    string $body,
    string $title,
    array $meta,
    Site $site,
    array $cssHashes = [],
    array $jsHashes = [],
  ): string {
    $ogTitle = $meta['og']['title'] ?? $title;
    $ogDescription = $meta['og']['description'] ?? ($meta['description'] ?? '');
    $ogImage = $meta['og']['image'] ?? '';
    
    $html = '<!DOCTYPE html>' . "\n";
    $html .= '<html lang="en">' . "\n";
    $html .= '<head>' . "\n";
    $html .= '  <meta charset="utf-8">' . "\n";
    $html .= '  <meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
    $html .= '  <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
    
    if (!empty($meta['description'])) {
      $html .= '  <meta name="description" content="' . htmlspecialchars($meta['description'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    
    // Open Graph
    $html .= '  <meta property="og:title" content="' . htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    if ($ogDescription) {
      $html .= '  <meta property="og:description" content="' . htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    if ($ogImage) {
      $html .= '  <meta property="og:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    $html .= '  <meta name="twitter:card" content="summary_large_image">' . "\n";
    
    // CSS
    foreach ($cssHashes as $hash) {
      $html .= '  <link rel="stylesheet" href="/css/' . $hash . '.css">' . "\n";
    }
    
    $html .= '</head>' . "\n";
    $html .= '<body>' . "\n";
    $html .= $body . "\n";
    
    // JS
    foreach ($jsHashes as $hash) {
      $html .= '  <script src="/js/' . $hash . '.js" defer></script>' . "\n";
    }
    
    $html .= '</body>' . "\n";
    $html .= '</html>';
    
    return $html;
  }
  
  /**
   * Collect unique assets from a rendered page into registries.
   */
   private function collectAssets(
     array $renderedPage,
     array &$cssRegistry,
     array &$jsRegistry,
     array &$imageRegistry
   ): void {
        // TODO: parse the HTML or maintain asset metadata

    foreach ($renderedPage['hashes']['css'] ?? [] as $hash) {
      if (!isset($cssRegistry[$hash])) {
        $cssRegistry[$hash] = [
          'name' => $hash,
          'content' => '', // TODO
          'hash' => $hash,
        ];
      }
    }
    
    foreach ($renderedPage['hashes']['js'] ?? [] as $hash) {
      if (!isset($jsRegistry[$hash])) {
        $jsRegistry[$hash] = [
          'name' => $hash,
          'content' => '', // TODO
          'hash' => $hash,
        ];
      }
    }
  }
    
  /**
   * Generate XML sitemap.
   */
  private function generateSitemap(Site $site, array $pages): string
  {
    $baseUrl = 'https://' . $site->getDomain();
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    foreach ($pages as $page) {
      $xml .= '  <url>' . "\n";
      $xml .= '    <loc>' . htmlspecialchars($baseUrl . $page['path'], ENT_XML1) . '</loc>' . "\n";
      $xml .= '    <changefreq>weekly</changefreq>' . "\n";
      $xml .= '  </url>' . "\n";
    }
    
    $xml .= '</urlset>';
    return $xml;
  }
  
  /**
   * Generate robots.txt.
   */
  private function generateRobots(Site $site): string
  {
    $baseUrl = 'https://' . $site->getDomain();
    return "User-agent: *\nAllow: /\nSitemap: {$baseUrl}/sitemap.xml\n";
  }
    
  /**
   * Generate unique build ID.
   */
  private function generateBuildId(): string
  {
    return substr(md5(uniqid('', true)), 0, 12);
  }
}
