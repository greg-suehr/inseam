<?php
namespace App\Import\DTO\Planning;

use App\Import\DTO\Planning\BlockNode;

final readonly class PagePlanItem
{
  public function __construct(
    public string $pageId,
    public string $sourceUrl,
    public BlockNode $blockTree,
    public string $sourceHash
  ) {}
}
