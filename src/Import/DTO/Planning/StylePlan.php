<?php

namespace App\Import\DTO\Planning;

final readonly class StylePlan
{
  /** @param string[] $compatCssScopes */
  public function __construct(
    public array $tokensUsed,
    public array $unmappedDeclarations,
    public string $scopedCompatibilityCss,
    public array $compatCssScopes = [] // e.g. ["compat-{$planId}"]
  ) {}
}
