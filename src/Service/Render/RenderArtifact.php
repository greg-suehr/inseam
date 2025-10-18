<?php

namespace App\Service\Render;

/**
 * Value object representing a complete site render output.
 */
final class RenderArtifact
{
  public function __construct(
    public readonly array $pages,
    public readonly array $css,
    public readonly array $js,
    public readonly array $images,
    public readonly ?string $sitemap,
    public readonly ?string $robots,
    public readonly array $manifest,
    public readonly ?string $redirects = null,
    public readonly ?string $headers = null,
  ) {}
  
  /**
   * Export artifact as associative array for serialization.
   */
  public function toArray(): array
  {
    return [
      'pages' => $this->pages,
      'css' => $this->css,
      'js' => $this->js,
      'images' => $this->images,
      'sitemap' => $this->sitemap,
      'robots' => $this->robots,
      'manifest' => $this->manifest,
      'redirects' => $this->redirects,
      'headers' => $this->headers,
    ];
  }
  
  /**
   * Find a page by path.
   */
  public function getPage(string $path): ?array
  {
    foreach ($this->pages as $page) {
      if ($page['path'] === $path) {
        return $page;
      }
    }
    return null;
  }
}
