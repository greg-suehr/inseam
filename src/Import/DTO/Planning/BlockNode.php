<?php
namespace App\Import\DTO\Planning;

abstract readonly class BlockNode
{
    /** @param BlockNode[] $children */
    public function __construct(public array $children = []) {}
}

final readonly class HeadingBlock extends BlockNode
{
    public function __construct(public int $level, public string $text) { parent::__construct([]); }
}

final readonly class ParagraphBlock extends BlockNode
{
    public function __construct(public string $text) { parent::__construct([]); }
}

final readonly class ImageBlock extends BlockNode
{
    public function __construct(
        public string $alt,
        public ?int $width,
        public ?int $height,
        public string $assetId // resolved later to URL
    ) { parent::__construct([]); }
}
