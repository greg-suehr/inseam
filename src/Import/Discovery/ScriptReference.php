<?php

namespace App\Import\Discovery;

final readonly class ScriptReference
{
  public function __construct(
    public string $url,
    public bool $inline,
    public ?string $content = null
  ) {}
}
