<?php

namespace App\Import\Storage;

use App\Import\DTO\Planning\AssetPlanItem;

interface AssetStorage
{
    /**
     * Fetch the source asset and store it at the target.
     * MUST return the final public URL that pages should use.
     */
    public function fetchAndStore(AssetPlanItem $asset): string;
}
