<?php

namespace App\Import\DTO\Planning;

abstract readonly class BlockNode
{
    /** @param BlockNode[] $children */
    public function __construct(public array $children = []) {}
}
