<?php

namespace App\Import\DTO\Planning;

final readonly class ScriptPlan
{
  /** @param string[] $quarantined @param string[] $nativeReplacements */
  public function __construct(
    public array $quarantined,
    public array $nativeReplacements
  ) {}
}
