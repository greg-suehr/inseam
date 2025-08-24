<?php

namespace App\Import\DTO\Planning;

final readonly class RoutePlanItem
{
  public function __construct(
    public string $pageId,
    public string $oldUrl,
    public string $slug,
    public string $route // e.g. "/pages/{slug}"
  ) {}
}
