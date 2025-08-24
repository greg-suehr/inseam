<?php

namespace App\Import\Util;

use App\Import\DTO\Planning\{AssetPlanItem, PagePlanItem, RoutePlanItem};
use App\Import\DTO\Planning\{BlockNode, HeadingBlock, ImageBlock, LinkBlock, ListBlock, ListItemBlock, ParagraphBlock, RootBlock};
use App\Import\DTO\Planning\{ImportPlan, ScriptPlan, StylePlan};
use App\Import\DTO\Planning\RedirectMap;

final class PlanHydrator
{
    public static function fromArray(array $data): ImportPlan
    {
        return new ImportPlan(
            planId: $data['planId'],
            routes: array_map([self::class, 'hydrateRoute'], $data['routes'] ?? []),
            pages: array_map([self::class, 'hydratePage'], $data['pages'] ?? []),
            assets: array_map([self::class, 'hydrateAsset'], $data['assets'] ?? []),
            styles: self::hydrateStylePlan($data['styles'] ?? []),
            scripts: self::hydrateScriptPlan($data['scripts'] ?? []),
            redirects: self::hydrateRedirectMap($data['redirects'] ?? [])
        );
    }

    public static function toArray(ImportPlan $plan): array
    {
        return [
            'planId' => $plan->planId,
            'routes' => array_map([self::class, 'serializeRoute'], $plan->routes),
            'pages' => array_map([self::class, 'serializePage'], $plan->pages),
            'assets' => array_map([self::class, 'serializeAsset'], $plan->assets),
            'styles' => self::serializeStylePlan($plan->styles),
            'scripts' => self::serializeScriptPlan($plan->scripts),
            'redirects' => self::serializeRedirectMap($plan->redirects)
        ];
    }

    private static function hydrateRoute(array $data): RoutePlanItem
    {
        return new RoutePlanItem(
            pageId: $data['pageId'],
            oldUrl: $data['oldUrl'],
            slug: $data['slug'],
            route: $data['route']
        );
    }

    private static function hydratePage(array $data): PagePlanItem
    {
        return new PagePlanItem(
            pageId: $data['pageId'],
            sourceUrl: $data['sourceUrl'],
            blockTree: self::hydrateBlockNode($data['blockTree']),
            sourceHash: $data['sourceHash']
        );
    }

    private static function hydrateAsset(array $data): AssetPlanItem
    {
        return new AssetPlanItem(
            assetId: $data['assetId'],
            sourceUrl: $data['sourceUrl'],
            expectedHash: $data['expectedHash'],
            targetPath: $data['targetPath']
        );
    }

    private static function hydrateStylePlan(array $data): StylePlan
    {
        return new StylePlan(
            tokensUsed: $data['tokensUsed'] ?? [],
            unmappedDeclarations: $data['unmappedDeclarations'] ?? [],
            scopedCompatibilityCss: $data['scopedCompatibilityCss'] ?? '',
            compatCssScopes: $data['compatCssScopes'] ?? []
        );
    }

    private static function hydrateScriptPlan(array $data): ScriptPlan
    {
        return new ScriptPlan(
            quarantined: $data['quarantined'] ?? [],
            nativeReplacements: $data['nativeReplacements'] ?? []
        );
    }

    private static function hydrateRedirectMap(array $data): RedirectMap
    {
        return new RedirectMap($data['map'] ?? []);
    }

    private static function hydrateBlockNode(array $data): BlockNode
    {
        $children = array_map([self::class, 'hydrateBlockNode'], $data['children'] ?? []);        
        
        return match ($data['type']) {
          'root' => new RootBlock($children),
          'heading' => new HeadingBlock($data['level'], $data['text']),
          'paragraph' => new ParagraphBlock($data['text']),
          'image' => new ImageBlock($data['alt'], $data['width'], $data['height'], $data['assetId']),
          'link' => new LinkBlock($data['text'], $data['href'], $data['rel'], $data['external']),
          'list' => new ListBlock(order: ($data['listType'] ?? 'unordered'), items: $children),
          'listItem' => new ListItemBlock($children),
          default => new ParagraphBlock('Unknown block type: ' . $data['type'])
        };
    }

    private static function serializeRoute(RoutePlanItem $route): array
    {
        return [
            'pageId' => $route->pageId,
            'oldUrl' => $route->oldUrl,
            'slug' => $route->slug,
            'route' => $route->route
        ];
    }

    private static function serializePage(PagePlanItem $page): array
    {
        return [
            'pageId' => $page->pageId,
            'sourceUrl' => $page->sourceUrl,
            'blockTree' => self::serializeBlockNode($page->blockTree),
            'sourceHash' => $page->sourceHash
        ];
    }

    private static function serializeAsset(AssetPlanItem $asset): array
    {
        return [
            'assetId' => $asset->assetId,
            'sourceUrl' => $asset->sourceUrl,
            'expectedHash' => $asset->expectedHash,
            'targetPath' => $asset->targetPath
        ];
    }

    private static function serializeStylePlan(StylePlan $styles): array
    {
        return [
            'tokensUsed' => $styles->tokensUsed,
            'unmappedDeclarations' => $styles->unmappedDeclarations,
            'scopedCompatibilityCss' => $styles->scopedCompatibilityCss,
            'compatCssScopes' => $styles->compatCssScopes
        ];
    }

    private static function serializeScriptPlan(ScriptPlan $scripts): array
    {
        return [
            'quarantined' => $scripts->quarantined,
            'nativeReplacements' => $scripts->nativeReplacements
        ];
    }

    private static function serializeRedirectMap(RedirectMap $redirects): array
    {
        return ['map' => $redirects->map];
    }

    private static function serializeBlockNode(BlockNode $block): array
    {
        $base = [
          'children' => array_map([self::class, 'serializeBlockNode'], $block->children)
        ];

        echo get_class($block) . "\n";

        return match (get_class($block)) {
          RootBlock::class => $base + ['type' => 'root'],
          HeadingBlock::class => $base + ['type' => 'heading', 'level' => $block->level, 'text' => $block->text],
          ParagraphBlock::class => $base + ['type' => 'paragraph', 'text' => $block->text],
          ImageBlock::class => $base + ['type' => 'image', 'alt' => $block->alt, 'width' => $block->width, 'height' => $block->height, 'assetId' => $block->assetId],
          LinkBlock::class => $base + ['type' => 'link', 'text' => $block->text, 'href' => $block->href, 'rel' => $block->rel, 'external' => $block->external],
          ListBlock::class => $base + ['type' => 'list', 'order' => $block->order],
          ListItemBlock::class => $base + ['type' => 'listItem'],
          default => $base + ['type' => 'unknown']
        };
    }
}
