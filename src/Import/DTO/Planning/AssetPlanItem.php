<?php
namespace App\Import\DTO\Planning;

final readonly class AssetPlanItem
{
    public function __construct(
        public string $assetId,
        public string $sourceUrl,
        public string $expectedHash,
        public string $targetPath // e.g. s3://bucket/hash.ext or /media/hash.ext
    ) {}
}
