<?php

namespace App\Import\Discovery;

final readonly class PageNode
{
  public function __construct(
    public string $id,
    public string $sourceUrl,
    public string $hash,
    public string $title,
    public array $meta,
    public string $rawHtml,
    public array $children = []
  ) {}
}
