<?php

namespace App\Import\DTO\ContentGraph;

final readonly class StylesheetNode extends Node
{
  public function __construct(
    string $id,
    string $sourceUrl,
    string $hash,
    public string $cssText
  ) {
        parent::__construct($id, $sourceUrl, $hash);
    }
}
