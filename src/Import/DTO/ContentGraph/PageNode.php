<?php

namespace App\Import\DTO\ContentGraph;

final readonly class PageNode extends Node
{
    public function __construct(
        string $id,
        string $sourceUrl,
        string $hash,
        public string $title,
        public array $meta,
        public string $rawHtml
    ) {
        parent::__construct($id, $sourceUrl, $hash);
  }
}
