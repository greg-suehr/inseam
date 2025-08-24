<?php

namespace App\Import\DTO\Planning;

final readonly class HeadingBlock extends BlockNode
{
    public function __construct(public int $level, public string $text) { parent::__construct([]); }
}
