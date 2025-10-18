<?php

namespace App\Service;

use App\Domain\Site;

/**
 * Renders PageEditor block trees into HTML.
 */
final class BlockRenderer
{
  /** @var array<string, callable> */
  private array $registry = [];
  
  public function __construct()
  {
    $this->registerDefaultBlocks();
  }

  /**
   * Render a block and its children recursively.
   */
  public function render(array $block, Site $site): string
  {
    $type = $block['type'] ?? 'unknown';
        
    if (!isset($this->registry[$type])) {
      return $this->renderUnknown($block);
    }
    
    $renderer = $this->registry[$type];
    return $renderer($block, $this, $site);
  }
  
  /**
   * Register a custom block renderer.
   */
  public function register(string $type, callable $renderer): void
  {
    $this->registry[$type] = $renderer;
  }
  
  /**
   * Escape HTML for safe output.
   */
  public function escape(string $text): string
  {
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }

  /**
   * Sanitize HTML content (basic implementation).
   * For production, consider using HTMLPurifier or similar.
   */
  public function sanitize(string $html): string
  {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);
    return $html ?? '';
  }

  /**
   * Register default block types from your roadmap.
   */
  private function registerDefaultBlocks(): void
  {
    // Hero block
    $this->register('hero', function(array $block, BlockRenderer $ctx, Site $site) {
            $heading = $ctx->escape($block['props']['heading'] ?? '');
            $subheading = $block['props']['subheading'] ?? '';
            $bgImage = $block['props']['backgroundImage'] ?? null;
            
            $html = '<section class="hero block-hero"';
            if (isset($block['id'])) {
              $html .= ' data-block-id="' . $ctx->escape($block['id']) . '"';
            }
            if ($bgImage) {
                $html .= ' style="background-image: url(\'' . $ctx->escape($bgImage) . '\')"';
            }
            $html .= '>';
            $html .= '<div class="hero-content">';
            $html .= "<h1>{$heading}</h1>";
            if ($subheading) {
              $html .= '<p class="hero-subheading">' . $ctx->escape($subheading) . '</p>';
            }
            $html .= '</div></section>';
            
            return $html;
    });
    
    // Rich text block
    $this->register('richText', function(array $block, BlockRenderer $ctx, Site $site) {
            $html = $block['props']['html'] ?? '';
            $blockId = isset($block['id']) ? ' data-block-id="' . $ctx->escape($block['id']) . '"' : '';
            
            return '<div class="rte block-rich-text"' . $blockId . '>' 
              . $ctx->sanitize($html) 
              . '</div>';
    });

    // Grid/columns block
    $this->register('grid', function(array $block, BlockRenderer $ctx, Site $site) {
            $columns = $block['props']['columns'] ?? 2;
            $children = $block['children'] ?? [];
            $blockId = isset($block['id']) ? ' data-block-id="' . $ctx->escape($block['id']) . '"' : '';
            
            $html = '<div class="grid cols-' . (int)$columns . ' block-grid"' . $blockId . '>';
            foreach ($children as $child) {
                $html .= $ctx->render($child, $site);
            }
            $html .= '</div>';
            
            return $html;
    });

    // Container block
    $this->register('container', function(array $block, BlockRenderer $ctx, Site $site) {
            $children = $block['children'] ?? [];
            $blockId = isset($block['id']) ? ' data-block-id="' . $ctx->escape($block['id']) . '"' : '';
            
            $html = '<div class="container block-container"' . $blockId . '>';
            foreach ($children as $child) {
                $html .= $ctx->render($child, $site);
            }
            $html .= '</div>';
            
            return $html;
    });

    // Image block
    $this->register('image', function(array $block, BlockRenderer $ctx, Site $site) {
            $src = $block['props']['src'] ?? '';
            $alt = $block['props']['alt'] ?? '';
            $caption = $block['props']['caption'] ?? null;
            $blockId = isset($block['id']) ? ' data-block-id="' . $ctx->escape($block['id']) . '"' : '';
            
            $html = '<figure class="block-image"' . $blockId . '>';
            $html .= '<img src="' . $ctx->escape($src) . '" alt="' . $ctx->escape($alt) . '" loading="lazy">';
            if ($caption) {
                $html .= '<figcaption>' . $ctx->escape($caption) . '</figcaption>';
            }
            $html .= '</figure>';
            
            return $html;
    });

    // Heading block
    $this->register('heading', function(array $block, BlockRenderer $ctx, Site $site) {
            $text = $block['props']['text'] ?? '';
            $level = min(6, max(1, (int)($block['props']['level'] ?? 2)));
            $blockId = isset($block['id']) ? ' data-block-id="' . $ctx->escape($block['id']) . '"' : '';
            
            return "<h{$level} class=\"block-heading\"{$blockId}>" 
              . $ctx->escape($text) 
              . "</h{$level}>";
    });
    
    // Button block
    $this->register('button', function(array $block, BlockRenderer $ctx, Site $site) {
            $text = $block['props']['text'] ?? 'Button';
            $url = $block['props']['url'] ?? '#';
            $style = $block['props']['style'] ?? 'primary';
            $blockId = isset($block['id']) ? ' data-block-id="' . $ctx->escape($block['id']) . '"' : '';
            
            return '<a href="' . $ctx->escape($url) . '" class="btn btn-' . $ctx->escape($style) . ' block-button"' . $blockId . '>' 
              . $ctx->escape($text) 
              . '</a>';
        });
    }

  /**
   * Fallback for unknown block types.
   */
  private function renderUnknown(array $block): string
  {
    $type = $this->escape($block['type'] ?? 'unknown');
    return "<!-- Unknown block type: {$type} -->";
  }
}
