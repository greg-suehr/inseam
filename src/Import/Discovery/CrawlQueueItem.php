<?php

namespace App\Import\Discovery;

final readonly class CrawlQueueItem
{
  public function __construct(
    public string $url,
    public int $depth
  ) {}
}
