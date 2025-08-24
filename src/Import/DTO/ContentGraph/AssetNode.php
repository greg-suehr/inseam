<?php

namespace App\Import\DTO\ContentGraph;

final readonly class AssetNode extends Node
{
  public function __construct(
    string $id,
    string $sourceUrl,
    string $hash,
    public AssetKind $kind,
    public string $contentType,
    public int $sizeBytes
  ) {
        parent::__construct($id, $sourceUrl, $hash);
    }
}
