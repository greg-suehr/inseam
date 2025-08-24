<?php

namespace App\Import\Planning;

use App\Import\DTO\Planning\BlockNode;
use App\Import\DTO\Planning\HeadingBlock;
use App\Import\DTO\Planning\ImageBlock;
use App\Import\DTO\Planning\PagePlanItem;
use App\Import\DTO\Planning\ParagraphBlock;
use App\Import\DTO\Planning\RootBlock;
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
            $this->logger->info('Writing page', [
                'route' => $route,
                'pageId' => $page->pageId,
                'sourceUrl' => $page->sourceUrl,
                'planId' => $provenance['planId'] ?? null
            ]);

            $content = $this->renderBlockTree($page->blockTree);

            $finalContent = $this->applyStyles($content, $styles);

            // TODO: Persist to your CMS
            // Example: Create Page entity with route, content, metadata
            // $pageEntity = new Page();            
            // $pageEntity->setRoute($route);
            // $pageEntity->setContent($finalContent);
            // $pageEntity->setProvenance($provenance);
            // $this->em->persist($pageEntity);
            
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
        default => '<p>Unknown block type</p>'
      };
      
      foreach ($block->children as $child) {
        $content .= $this->renderBlockTree($child);
      }
      
      return $content;
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
