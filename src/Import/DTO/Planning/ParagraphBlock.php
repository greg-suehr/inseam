<?php

namespace App\Import\DTO\Planning;

final readonly class ParagraphBlock extends BlockNode
{
    public function __construct(public string $text) { parent::__construct([]); }
}
