<?php
namespace App\Import\DTO;

final readonly class ImportSource
{
    public function __construct(
        public string $entryUrlOrFile,
        public ?Credentials $credentials = null,
        public ?string $platform = null, // "static","squarespace","wix"
        public ?string $siteId = null,   // tenant/site scope
    ) {}
}

final readonly class Credentials
{
    public function __construct(
        public ?string $username = null,
        public ?string $password = null,
        public ?string $apiKey   = null
    ) {}
}
