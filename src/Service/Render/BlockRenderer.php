<?php

namespace App\Service\Render;

use App\Entity\Site;

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
    // Paragraph block
    $this->register('paragraph', function(array $block, BlockRenderer $ctx, Site $site) {
      $text = $block['text'] ?? ($block['props']['text'] ?? '');
      $blockId = isset($block['id']) ? ' data-block-id="' . $ctx->escape($block['id']) . '"' : '';
      
      return '<p class="block-paragraph"' . $blockId . '>' 
        . $ctx->escape($text) 
        . '</p>';
    });

    // Link block - standalone link styled as button or card
    $this->register('link', function(array $block, BlockRenderer $ctx, Site $site) {
      $href = $block['href'] ?? ($block['props']['href'] ?? '#');
      $text = $block['text'] ?? ($block['props']['text'] ?? 'Link');
      $external = $block['external'] ?? ($block['props']['external'] ?? false);
      $blockId = isset($block['id']) ? ' data-block-id="' . $ctx->escape($block['id']) . '"' : '';
      
      $attrs = '';
      if ($external) {
        $attrs = ' rel="noopener noreferrer" target="_blank"';
      }
      
      return '<p class="links block-link"' . $blockId . '>'
        . '<a href="' . $ctx->escape($href) . '"' . $attrs . '>'
        . $ctx->escape($text)
        . '</a>'
        . '</p>';
    });

    $this->register('section', function(array $block, BlockRenderer $ctx, Site $site) {
      $children = $block['children'] ?? [];
      $blockId = isset($block['id']) ? ' data-block-id="' . $ctx->escape($block['id']) . '"' : '';
      $className = $block['className'] ?? ($block['props']['className'] ?? '');
      
      $classAttr = 'block-section';
      if ($className) {
        $classAttr .= ' ' . $ctx->escape($className);
      }
      
      $html = '<section class="' . $classAttr . '"' . $blockId . '>';
      foreach ($children as $child) {
        $html .= $ctx->render($child, $site);
      }
      $html .= '</section>';
      
      return $html;
    });

    // List block - ordered or unordered
    $this->register('list', function(array $block, BlockRenderer $ctx, Site $site) {
      $items = $block['data']['items'] ?? ($block['props']['data']['items'] ?? []);
      $order = $block['data']['order'] ?? ($block['props']['data']['order'] ?? false);
      $blockId = isset($block['id']) ? ' data-block-id="' . $ctx->escape($block['id']) . '"' : '';
      
      if (empty($items)) {
        return '<!-- Empty list block -->';
      }
      
      $html = "<{$order} class=\"block-list\"{$blockId}>";
      
      foreach ($items as $item) {
        $itemText = is_string($item) ? $item : ($item['text'] ?? '');
        $html .= '<li>' . $ctx->escape($itemText) . '</li>';
      }
      
      $html .= "</{$order}>";
      return $html;
    });
    
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
