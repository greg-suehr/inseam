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

final readonly class RoutePlanItem
{
    public function __construct(
        public string $pageId,
        public string $oldUrl,
        public string $slug,
        public string $route, // e.g. /pages/{slug}
    ) {}
}

final readonly class PagePlanItem
{
    public function __construct(
        public string $pageId,
        public string $sourceUrl,
        /** BlockNode tree (see below) */
        public BlockNode $blockTree,
        public string $sourceHash
    ) {}
}

final readonly class AssetPlanItem
{
    public function __construct(
        public string $assetId,
        public string $sourceUrl,
        public string $expectedHash,
        public string $targetPath // e.g. s3://bucket/hash.ext or /media/hash.ext
    ) {}
}

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

final readonly class ScriptPlan
{
    /** @param string[] $quarantined @param string[] $nativeReplacements */
    public function __construct(
        public array $quarantined,
        public array $nativeReplacements
    ) {}
}

final readonly class RedirectMap
{
    /** @param array<string,string> $map oldUrl => newRoute */
    public function __construct(public array $map) {}
}
