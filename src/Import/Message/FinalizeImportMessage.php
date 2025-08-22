<?php
namespace App\Import\Message;

final readonly class FinalizeImportMessage
{
    public function __construct(public string $planId, public string $siteId) {}
}
