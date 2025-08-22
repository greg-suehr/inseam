<?php
namespace App\Import\Planning;

use App\Import\DTO\Planning\StylePlan;

final class StyleTokenizationEngine
{
    public function tokenize(array $stylesheets): StylePlan
    {
        return new StylePlan(tokensUsed: [], unmappedDeclarations: [], scopedCompatibilityCss: '', compatCssScopes: []);
    }
}
