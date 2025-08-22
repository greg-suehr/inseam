<?php
namespace App\Import\Message;

final readonly class ExecutePageMessage
{
    public function __construct(public string $planId, public string $pageId, public string $siteId) {}
}
