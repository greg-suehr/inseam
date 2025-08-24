<?php

namespace App\Import\DTO\Planning;

final readonly class ListItemBlock extends BlockNode
{
  /** @param BlockNode[] $itemBlocks */
  public function __construct(
    array $itemBlocks = []
  )
  {
      parent::__construct(
        $itemBlocks
      );
    }
}
