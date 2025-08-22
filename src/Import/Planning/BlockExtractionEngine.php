<?php
namespace App\Import\Planning;

final class BlockExtractionEngine
{
    /** Accept raw HTML and return a BlockNode tree */
    public function extract(string $html): \App\Import\DTO\Planning\BlockNode
    {
        // TODO: DOM → blocks
        return new \App\Import\DTO\Planning\ParagraphBlock('TODO');
    }
}
