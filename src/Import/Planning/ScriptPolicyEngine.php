<?php
namespace App\Import\Planning;

use App\Import\DTO\Planning\ScriptPlan;

final class ScriptPolicyEngine
{
    public function classify(array $scripts): ScriptPlan
    {
        return new ScriptPlan(quarantined: [], nativeReplacements: []);
    }
}
