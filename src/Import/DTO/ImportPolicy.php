<?php
namespace App\Import\DTO;

final readonly class ImportPolicy
{
    public function __construct(
        public bool $respectRobotsTxt = true,
        public bool $followSubdomains = false,
        public int  $maxPages = 500,
        public bool $enableHeadlessBrowser = true,
        public bool $quarantineThirdPartyScripts = true
    ) {}
}
