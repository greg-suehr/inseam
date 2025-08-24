<?php

namespace App\Import\Planning;

use App\Entity\PageContent;
use App\Import\DTO\Planning\BlockNode;
use App\Import\DTO\Planning\{HeadingBlock, ImageBlock, LinkBlock, ListBlock, ListItemBlock, ParagraphBlock, RootBlock};
use App\Import\DTO\Planning\PagePlanItem;
use App\Import\DTO\Planning\StylePlan;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class PageWriter
{
  public function __construct(
    private EntityManagerInterface $em,
    private LoggerInterface $logger,
  ) {}
  
    public function upsert(string $route, PagePlanItem $page, StylePlan $styles, array $provenance): void
    {
        try {
          $planId = (string)($provenance['planId'] ?? '');
          
          $this->logger->info('Writing page', [
            'route' => $route,
            'pageId' => $page->pageId,
            'sourceUrl' => $page->sourceUrl,
            'planId' => $planId ?? null
          ]);
          
          $content = $this->renderBlockTree($page->blockTree);
          
          $finalContent = $this->applyStyles($content, $styles);

          $repo = $this->em->getRepository(PageContent::class);
          $row = $repo->findOneBy(['planId' => $planId, 'route' => $route]);
          
          if ($row) {
            $row->setContent($finalContent);
            $row->setProvenance($provenance);
          } else {
            $row = new PageContent($planId, $route, $finalContent, $provenance);
          }
          
          $this->em->persist($row);
          $this->em->flush();
          
          $this->logger->info('Page written successfully', ['route' => $route]);
          
        } catch (\Exception $e) {
          $this->logger->error('Failed to write page', [
            'route' => $route,
            'error' => $e->getMessage(),
            'pageId' => $page->pageId
          ]);
          throw $e;
        }
    }

  private function renderBlockTree(BlockNode $block): string
  {
      # Render children first, inside container element
      if ($block instanceof RootBlock) {
        return $this->renderChildren($block->children);
      }

      if ($block instanceof ListBlock) {
        $tag = $block->order;
        
        $itemsHtml = '';
        foreach ($block->children as $item) {
          $itemsHtml .= $this->renderListItem($item);
        }
        
        return sprintf('<%1$s>%2$s</%1$s>', $tag, $itemsHtml);
      }

      if ($block instanceof ListItemBlock) {
        return $this->renderListItem($block);
      }
      
      $content = match (get_class($block)) {
        RootBlock::class      => '',
        HeadingBlock::class => sprintf('<h%d>%s</h%d>', $block->level, htmlspecialchars($block->text), $block->level),
        ParagraphBlock::class => sprintf('<p>%s</p>', htmlspecialchars($block->text)),
        ImageBlock::class => sprintf('<img src="ASSET:%s" alt="%s"%s%s />',
                                     $block->assetId,
                                     htmlspecialchars($block->alt),
                                     $block->width ? " width=\"{$block->width}\"" : '',
                                     $block->height ? " height=\"{$block->height}\"" : ''                                    
        ),
        LinkBlock::class => sprintf('<a href="%s" %s>%s</a>',
                                    htmlspecialchars($block->href, ENT_QUOTES),
                                    $block->external ? 'rel="noopener noreferrer" target="_blank"' : '',
                                    htmlspecialchars($block->text)
        ),
        default => '<p>Unknown block type</p>'
      };
      
      foreach ($block->children as $child) {
        $content .= $this->renderBlockTree($child);
      }
      
      return $content;
  }

  private function renderChildren(array $children): string
  {
    $out = '';
    foreach ($children as $child) {
      $out .= $this->renderBlockTree($child);
    }
    return $out;
  }

  private function renderListItem(ListItemBlock $item): string
  {
    $inner = $this->renderChildren($item->children ?? []);
    return "<li>{$inner}</li>";
  }
  
  private function applyStyles(string $content, StylePlan $styles): string
  {
      if (!empty($styles->scopedCompatibilityCss)) {
        $scope = $styles->compatCssScopes[0] ?? 'compat-default';
        return sprintf(
          '<div class="%s">%s</div><style>%s</style>',
          htmlspecialchars($scope),
          $content,
          $styles->scopedCompatibilityCss
        );
      }
      return $content;
    }
}
