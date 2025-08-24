<?php

namespace App\Import\DTO\Planning;

final readonly class ListBlock extends BlockNode
{
  /** @param string[] $items */
  public function __construct(
    public string $order = 'ul',
    public array  $items = []
  )
  {
    parent::__construct($items);
  }
}
