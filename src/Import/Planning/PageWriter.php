<?php // src/Import/Planning/PageWriter.php
namespace App\Import\Planning;

use App\Import\DTO\Planning\PagePlanItem;
use App\Import\DTO\Planning\StylePlan;

final class PageWriter
{
    public function upsert(string $route, PagePlanItem $page, StylePlan $styles, array $provenance): void
    {
        // Persist your CMS Page + Blocks; mark provenance
    }
}
