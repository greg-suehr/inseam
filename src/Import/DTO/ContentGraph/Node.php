<?php
namespace App\Import\DTO\ContentGraph;

abstract readonly class Node
{
    public function __construct(
      public string $id,
      public string $sourceUrl,
      public ?string $hash = null, 
    ) {}
}
