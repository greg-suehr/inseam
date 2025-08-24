<?php

namespace App\Import\DTO\ContentGraph;

final readonly class Edge
{
  public function __construct(
    public string $fromId,
    public string $toId,
    public string $type
  ) {}
}
