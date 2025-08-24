<?php
namespace App\Import\Planning;

use App\Import\DTO\Planning\BlockNode;
use App\Import\DTO\Planning\ParagraphBlock;
use App\Import\DTO\Planning\HeadingBlock;
use App\Import\DTO\Planning\ImageBlock;

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
       
       foreach (['//script', '//style'] as $q) {
         foreach ($xpath->query($q) as $node) {
           $node->parentNode?->removeChild($node);
         }
       }
       
       $chunks = [];

       // Headings first
       foreach (['//h1', '//h2', '//h3'] as $q) {
         foreach ($xpath->query($q) as $n) {
           $t = trim($n->textContent);
           if ($t !== '') $chunks[] = $t;
         }
         if (!empty($chunks)) break; // take highest available rank
       }
       
       // Paragraphs, quotes, list items, figcaptions
       foreach (['//p', '//blockquote', '//li', '//figcaption'] as $q) {
         foreach ($xpath->query($q) as $n) {
           $t = trim(preg_replace('/\s+/u', ' ', $n->textContent ?? ''));
           if ($t !== '') $chunks[] = $t;

           if (count($chunks) >= 40) break 2; // avoid megablobs
         }
       }

       // If we found readable text, collapse to one ParagraphBlock
       if (!empty($chunks)) {
         $text = trim(self::collapse($chunks));
         if ($text !== '') {
           return new ParagraphBlock($text);
         }
       }

       $img = $xpath->query('//img[@src]')?->item(0);
       if ($img instanceof \DOMElement) {
         $src = trim($img->getAttribute('src'));
         if ($src !== '') {
           $assetId = substr(hash('sha256', $src), 0, 16); // placeholder
           $alt     = $img->getAttribute('alt') ?? '';
           $wAttr   = $img->getAttribute('width');
           $hAttr   = $img->getAttribute('height');
           $width   = ($wAttr !== '') ? (int)$wAttr : null;
           $height  = ($hAttr !== '') ? (int)$hAttr : null;
           
           return new ImageBlock(
             alt: $alt,
             width: $width,
             height: $height,
             assetId: $assetId
           );
         }
       }

       foreach (['//h4', '//h5', '//h6'] as $q) {
         $n = $xpath->query($q)?->item(0);
         if ($n instanceof \DOMElement) {
           $lvl = (int) substr($n->tagName, 1);
           $txt = trim($n->textContent ?? '');
           if ($txt !== '') return new HeadingBlock($lvl, $txt);
         }
       }
       
       return new ParagraphBlock('');
    }

  /** @param string[] $chunks */
  private static function collapse(array $chunks): string
  {
      $s = implode('  ', array_map(
        fn ($t) => preg_replace('/\s+/u', ' ', trim($t)),
        $chunks
      ));
      
      // Hard cap to planning artifact size
      if (mb_strlen($s) > 8000) {
        $s = mb_substr($s, 0, 8000) . '…';
      }
      return $s;
    }
}
