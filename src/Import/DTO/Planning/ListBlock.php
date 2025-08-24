<?php

namespace App\Import\DTO\Planning;

final readonly class ListBlock extends BlockNode
{
  /** @param string[] $items */
  public function __construct(
    string $type = "unordered",
    array  $items = []
  )
  {
    parent::__construct([]);
  }
}
