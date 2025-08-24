<?php

namespace App\Import\Discovery;

use App\Import\DTO\ContentGraph\AssetKind;

final readonly class AssetReference
{
  public function __construct(
    public string $url,
    public AssetKind $kind,
    public array $attributes = []
  ) {}
}
