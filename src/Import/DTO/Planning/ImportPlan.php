<?php

namespace App\Import\DTO\Planning;

final readonly class ImportPlan
{
    /**
     * @param array<string,RoutePlanItem> $routes
     * @param array<string,PagePlanItem>  $pages
     * @param array<string,AssetPlanItem> $assets
     */
    public function __construct(
        public string $planId,
        public array $routes,
        public array $pages,
        public array $assets,
        public StylePlan $styles,
        public ScriptPlan $scripts,
        public RedirectMap $redirects
    ) {}
}
