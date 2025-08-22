<?php
namespace App\Import\Planning;

final class LinkRewriter
{
    public function swapAssetReferences(string $planId, string $assetId, string $finalUrl): void
    {
        // Find stored pages/blocks that reference assetId and update to $finalUrl
    }
}
