<?php
namespace App\Import;

final readonly class ImporterCapabilities
{
    public function __construct(
        public bool $supportsHeadless,
        public bool $supportsExportFiles,
        /** @var string[] */
        public array $knownWidgets = []
    ) {}
}
