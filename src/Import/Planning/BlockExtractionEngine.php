<?php
namespace App\Import\Planning;

use App\Import\DTO\Planning\BlockNode;
use App\Import\DTO\Planning\HeadingBlock;
use App\Import\DTO\Planning\ImageBlock;
use App\Import\DTO\Planning\ParagraphBlock;
use App\Import\DTO\Planning\RootBlock;

final class BlockExtractionEngine
{
  /** Accept raw HTML and return a BlockNode tree */
  public function extract(string $html): BlockNode
  {
    $doc = new \DOMDocument();
    $old = libxml_use_internal_errors(true);
    $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_use_internal_errors($old);
    
    $xpath = new \DOMXPath($doc);
    
    // Remove obvious chrome/noise up front
    $this->removeNoiseElements($xpath);
    
    // Choose a sensible root to traverse (main > article > largest texty node)
    $root = $this->pickContentRoot($xpath, $doc);
    
    $blocks = $this->buildBlocks($root);
    
    // Fallback if we somehow got nothing
    if (empty($blocks)) {
      $text = $this->blockText($root);
      if ($text !== '') {
        $blocks[] = new ParagraphBlock($text);
      }
    }
    
    return new RootBlock($blocks);
  }
    
  private function removeNoiseElements(\DOMXPath $xpath): void
  {
    // Remove elements that add noise
    $noiseSelectors = [
      '//script',
      '//style', 
      '//noscript',
      '//header[not(contains(@class,"content"))]',
      '//footer',
      '//nav','//aside',
      '//*[contains(translate(@class,"OVERLAY","overlay"),"overlay")]',
    ];
    
    foreach ($noiseSelectors as $selector) {
      foreach ($xpath->query($selector) as $node) {
        $node->parentNode?->removeChild($node);
      }
    }
  }
    
  private function extractHeadings(\DOMXPath $xpath): array
  {
    $headings = [];
        
    // Look for headings in main content areas first
    $headingQueries = [
      '//main//h1 | //main//h2 | //main//h3 | //main//h4 | //main//h5 | //main//h6',
      '//article//h1 | //article//h2 | //article//h3 | //article//h4 | //article//h5 | //article//h6',
      '//*[@class and contains(@class, "content")]//h1 | //*[@class and contains(@class, "content")]//h2 | //*[@class and contains(@class, "content")]//h3',
      '//h1 | //h2 | //h3 | //h4 | //h5 | //h6'  // fallback
    ];
    
    foreach ($headingQueries as $query) {
      $nodes = $xpath->query($query);
      if ($nodes->length > 0) {
        foreach ($nodes as $node) {
          $text = $this->extractCleanText($node);
          if ($text && !$this->isNavigationText($text)) {
            $level = (int) substr(strtolower($node->tagName), 1);
            $headings[] = new HeadingBlock($level, $text);
          }
        }
        break; // Use first successful query that found headings
      }
    }
    
    return $headings;
    }
  
  private function extractImages(\DOMXPath $xpath): array
  {
    $images = [];
    
    // Focus on content images, not decorative ones
    $imageQueries = [
      '//main//img[@src]',
      '//article//img[@src]',
      '//*[@class and contains(@class, "content")]//img[@src]',
      '//img[@src and @alt]' // Images with alt text are likely content
    ];
    
    $seenSrcs = [];
    
    foreach ($imageQueries as $query) {
      $nodes = $xpath->query($query);
      foreach ($nodes as $img) {
        $src = trim($img->getAttribute('src'));
        if ($src && !isset($seenSrcs[$src])) {
          $seenSrcs[$src] = true;
          
          $alt = $img->getAttribute('alt') ?? '';
          $width = $img->getAttribute('width') ? (int)$img->getAttribute('width') : null;
          $height = $img->getAttribute('height') ? (int)$img->getAttribute('height') : null;
          $assetId = 'asset_' . hash('sha256', $src);
          
          $images[] = new ImageBlock($alt, $width, $height, $assetId);
        }
      }
    }
    
    return $images;
    }
  
  private function extractMeaningfulParagraphs(\DOMXPath $xpath): array
  {
    $paragraphs = [];
    
    // Look for substantial text content
    $paragraphQueries = [
      '//main//p',
      '//article//p', 
      '//*[@class and contains(@class, "content")]//p',
      '//div[string-length(normalize-space(text())) > 50]', // Divs with substantial text
      '//p'
    ];
    
    foreach ($paragraphQueries as $query) {
      $nodes = $xpath->query($query);
      if ($nodes->length > 0) {
        foreach ($nodes as $node) {
          $text = $this->extractCleanText($node);
          if ($this->isValidParagraphText($text)) {
            $paragraphs[] = new ParagraphBlock($text);
          }
        }
        
        // If we found good paragraphs in a main content area, don't look elsewhere
        if (!empty($paragraphs) && str_contains($query, 'main')) {
                    break;
        }
      }
    }
    
    return $paragraphs;
    }
  
  private function isValidParagraphText(string $text): bool
  {
      if (strlen($text) < 20) {
        return false;
      }
      
      // Skip if it's mostly contact info or navigation
      if ($this->isNavigationText($text)) {
        return false;
      }
      
      // Skip if it's repetitive contact information
      if (preg_match('/sra\.acting@gmail\.com.*chess\.com.*Instagram/i', $text)) {
        return false;
      }
      
      // Skip if it's just links or short phrases
      if (substr_count($text, '@') > 0 && strlen($text) < 100) {
        return false;
      }
      
      return true;
    }
  
  private function extractFallbackContent(\DOMXPath $xpath): ?BlockNode
  {
      // Look for the longest text block that isn't navigation
      $textBlocks = $xpath->query('//div | //section | //article');
      $bestText = '';
      $bestLength = 0;
      
      foreach ($textBlocks as $block) {
        $text = $this->extractCleanText($block);
        if (strlen($text) > $bestLength && $this->isValidParagraphText($text)) {
          $bestText = $text;
          $bestLength = strlen($text);
        }
      }
      
      return $bestText ? new ParagraphBlock($bestText) : null;
    }
  
  private function extractCleanText(\DOMElement $node): string
  {
      $text = $node->textContent ?? '';
      $text = $this->normalizeWhitespace($text);
      $text = trim($text);
      
      // Remove common repeated elements within the text
      $text = preg_replace('/\b(sra\.acting@gmail\.com|chess\.com|Instagram)\s*/i', '', $text);
      $text = preg_replace('/\s+/', ' ', $text); // Re-normalize after removal
      $text = trim($text);
      
      // Apply reasonable length limits
      if (mb_strlen($text) > 2000) {
        $text = mb_substr($text, 0, 2000) . '…';
      }
      
      return $text;
    }
  
  private function normalizeWhitespace(string $text): string
  {
    return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }
    
  private function isNavigationText(string $text): bool
  {
    $navPatterns = [
      '/^(home|about|contact|menu|nav|skip|search|login|signup)$/i',
      '/^(copyright|©|\d{4}|\w+\s+\d{4})$/i',
      '/^(click|read more|learn more)$/i',
      '/^[a-zA-Z\s]{1,30}$/', // Very short text is likely navigation
      '/@gmail\.com/',         // Email addresses
      '/instagram|chess\.com/i', // Social links
    ];
    
    foreach ($navPatterns as $pattern) {
      if (preg_match($pattern, trim($text))) {
        return true;
      }
    }
    
    return false;
  }

  /** Choose main content root without breaking if it's missing */
  private function pickContentRoot(\DOMXPath $xpath, \DOMDocument $doc): \DOMNode
  {
    foreach (['//main', '//article', '//*[@id="content"]', '//*[contains(@class, "content")]'] as $q) {
      $n = $xpath->query($q)?->item(0);
      if ($n instanceof \DOMNode) return $n;
    }

    $candidates = $xpath->query('//div | //section | //body');
    $best = $doc->documentElement;
    $bestLen = 0;
    foreach ($candidates as $cand) {
      $len = mb_strlen($this->blockText($cand, 10000));
      if ($len > $bestLen) { $bestLen = $len; $best = $cand; }
    }
    return $best ?? $doc;
  }

  /** Depth-first traversal yielding BlockNodes in source order */
  private function buildBlocks(\DOMNode $node, int $cap = 200): array
  {
    $out = [];
    $count = 0;
    
    $walker = function(\DOMNode $n) use (&$out, &$walker, $cap, &$count) {
        if ($count >= $cap) return;
        
        if ($n instanceof \DOMElement) {
          $tag = strtolower($n->tagName);
          
          // Skip structural elements that slipped through
          if (in_array($tag, ['nav','header','footer','aside'])) return;
          
          // Map headings
          if (preg_match('/^h([1-6])$/', $tag, $m)) {
            $txt = $this->inlineText($n);
            if ($txt !== '') {
              $out[] = new HeadingBlock((int)$m[1], $txt);
              $count++;
            }
            return; // headings are leaf in our block model
          }
          
          // Paragraph
          if ($tag === 'p') {
            $txt = $this->inlineText($n);
            if ($this->isSubstantive($txt)) {
              $out[] = new ParagraphBlock($txt);
              $count++;
            }
            return;
          }
          
          if ($tag === 'blockquote') {
            $children = $this->collectChildrenBlocks($n);
            if (!empty($children)) {
              $out[] = new QuoteBlock($children); // TODO: implement QuoteBlock
              $count++;
            }
            return;
          }
          
          // Lists
          if ($tag === 'ul' || $tag === 'ol') {
            $items = [];
            /** @var \DOMElement $li */
            foreach ($n->getElementsByTagName('li') as $li) {
              // Only direct children to preserve nesting properly
              if ($li->parentNode !== $n) continue;
              $itemBlocks = $this->collectChildrenBlocks($li);
              if (empty($itemBlocks)) {
                $txt = $this->inlineText($li);
                if ($this->isSubstantive($txt)) {
                  $itemBlocks = [new ParagraphBlock($txt)];
                }
              }
              if (!empty($itemBlocks)) $items[] = new ListItemBlock($itemBlocks);
            }
            if (!empty($items)) {
              $out[] = new ListBlock(type: ($tag === 'ol' ? 'ordered' : 'unordered'), items: $items);
              $count++;
            }
            return;
          }
          
          // Figure with optional figcaption
          if ($tag === 'figure') {
            $imgEl = $this->firstDescendant($n, 'img');
            if ($imgEl instanceof \DOMElement && $imgEl->hasAttribute('src')) {
              $out[] = $this->imageToBlock($imgEl, $this->firstDescendant($n, 'figcaption'));
              $count++;
              return;
            }
          }
          
          // Standalone image
          if ($tag === 'img' && $n->hasAttribute('src')) {
            $out[] = $this->imageToBlock($n, null);
            $count++;
            return;
          }
          
          // Code/pre
          if ($tag === 'pre' || $tag === 'code') {
            $txt = $this->preservePreText($n);
            if ($txt !== '') {
              $out[] = new CodeBlock($txt);
              $count++;
            }
            return;
          }
          
          // Horizontal rule
          if ($tag === 'hr') {
            $out[] = new DividerBlock();
            $count++;
            return;
          }
          
          // Generic block container: recurse into children, but DON’T merge across boundaries
          if (in_array($tag, ['div','section','article','main'])) {
            foreach (iterator_to_array($n->childNodes) as $child) {
              $walker($child);
              if ($count >= $cap) break;
            }
            return;
          }
        }
        
        // Text node directly under root-ish container → paragraphize
        if ($n instanceof \DOMText) {
          $txt = $this->normalizeWhitespace($n->wholeText ?? '');
          if ($this->isSubstantive($txt)) {
            $out[] = new ParagraphBlock($txt);
            $count++;
          }
        }
        
        foreach (iterator_to_array($n->childNodes) as $child) {
          $walker($child);
          if ($count >= $cap) break;
        }
        
        return;
    };
    
    $walker($node);
    return $out;
  }
  
  /** Gather blocks for a node’s children without crossing block boundaries */
  private function collectChildrenBlocks(\DOMNode $node): array
  {
    $acc = [];
    foreach (iterator_to_array($node->childNodes) as $c) {
      $childBlocks = $this->buildBlocks($c, cap: 1_000);
      foreach ($childBlocks as $b) $acc[] = $b;
    }
    
    return $acc;
  }
  
  private function inlineText(\DOMElement $el): string
  {
    // Turn <br> into line breaks
    $clone = $el->cloneNode(true);
    foreach ($clone->getElementsByTagName('br') as $br) { $br->nodeValue = "\n"; }
    $txt = $clone->textContent ?? '';
    $txt = preg_replace('/[ \t]+/u', ' ', str_replace(["\r"], '', $txt));
    $txt = preg_replace("/\n{3,}/", "\n\n", $txt);
    $txt = trim($txt);
    // Cap per-block to keep artifacts small
    if (mb_strlen($txt) > 4000) $txt = mb_substr($txt, 0, 4000) . '…';
    return $txt;
  }

  private function preservePreText(\DOMElement $el): string
  {
    // For <pre>/<code>, preserve newlines and indentation
    $txt = $el->textContent ?? '';  
    return rtrim($txt, "\n\r ");
  }
  
  private function isSubstantive(string $s): bool
  {
    if ($s === '') return false;
    
    // length threshold, but allow shorter when sentence-like
    if (mb_strlen($s) < 20 && !preg_match('/[.!?]\s*$/u', $s)) return false;
    
    $nav = '/^(home|about|contact|menu|search|login|sign ?up)$/i';
    if (preg_match($nav, trim($s))) return false;
    
    return true;
  }

  private function firstDescendant(\DOMNode $node, string $tag): ?\DOMElement
  {
    foreach ($node->getElementsByTagName($tag) as $el) return $el;
    return null;
  }

  private function imageToBlock(\DOMElement $img, ?\DOMElement $figcaption): ImageBlock|FigureBlock
  {
    $src = trim($img->getAttribute('src'));
    $alt = $img->getAttribute('alt') ?? '';
    $w   = $img->getAttribute('width')  !== '' ? (int)$img->getAttribute('width')  : null;
    $h   = $img->getAttribute('height') !== '' ? (int)$img->getAttribute('height') : null;
    $assetId = substr(hash('sha256', $src), 0, 16);

    $image = new ImageBlock(alt: $alt, width: $w, height: $h, assetId: $assetId);

    if ($figcaption instanceof \DOMElement) {
        $cap = $this->inlineText($figcaption);
        if ($cap !== '') return new FigureBlock($image, $cap);
    }
    return $image;
  }
}
