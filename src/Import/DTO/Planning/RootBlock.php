<?php

namespace App\Import\DTO\Planning;

final readonly class RootBlock extends BlockNode
{
  /** @param BlockNode[] $children */
  public function __construct(array $children = [])
  {
      parent::__construct($children);
    }
}
