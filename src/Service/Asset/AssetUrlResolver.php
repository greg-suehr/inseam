<?php

namespace App\Service\Asset;

final class AssetUrlResolver
{
  public function __construct(private string $assetBase = '/import-assets') {}
  
  public function urlFor(string $assetId): string
  {
    return sprintf('%s/%s', rtrim($this->assetBase, '/'), $assetId);
  }
}
