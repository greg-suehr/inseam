<?php
namespace App\Import\Message;

final readonly class ExecuteAssetMessage
{
    public function __construct(public string $planId, public string $assetId, public string $siteId) {}
}
