<?php
namespace App\Import\Planning;

use App\Import\DTO\Planning\RoutePlanItem;

final class RoutingEngine
{
    /** @param RoutePlanItem $rp */
    public function resolve(RoutePlanItem $rp): string
    {
        // produce final internal route/path
        return $rp->route;
    }
}
