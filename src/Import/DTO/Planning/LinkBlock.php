<?php

namespace App\Import\DTO\Planning;

final readonly class LinkBlock extends BlockNode
{
    public function __construct(
      public string $text,      
      public string $href,
      public ?string $rel = null,
      public bool $external = false,
    ) {
        parent::__construct([]);
    }
}
