<?php

namespace App\Import\DTO\ContentGraph;

final readonly class ScriptNode extends Node
{
  public function __construct(
    string $id,
    string $sourceUrl,
    string $hash,
    public string $jsText,
    public bool $inline
  ) {
      parent::__construct($id, $sourceUrl, $hash);
    }
}
