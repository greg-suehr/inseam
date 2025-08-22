<?php
namespace App\Import\DTO;

use App\Import\DTO\ContentGraph\Node;
use App\Import\DTO\ContentGraph\Edge;

final readonly class DiscoveryResult
{
    /** @param Node[] $nodes @param Edge[] $edges */
    public function __construct(
        public string $graphId,
        public array $nodes,
        public array $edges
    ) {}
}
