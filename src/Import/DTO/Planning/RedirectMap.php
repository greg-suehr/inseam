<?php

namespace App\Import\DTO\Planning;

final readonly class RedirectMap
{
    /** @param array<string,string> $map oldUrl => newRoute */
    public function __construct(public array $map) {}
}
