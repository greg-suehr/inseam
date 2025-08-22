<?php
namespace App\Import\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Import\Message\ExecuteAssetMessage;
use App\Import\Planning\PlanRepository;
use App\Import\Storage\AssetStorage;
use App\Import\Planning\LinkRewriter;
use App\Import\Tenancy\TenantContext;

#[AsMessageHandler]
final class ExecuteAssetHandler
{
    public function __construct(
        private TenantContext $tenants,
        private PlanRepository $plans,
        private AssetStorage $storage,
        private LinkRewriter $links
    ) {}

    public function __invoke(ExecuteAssetMessage $m): void
    {
        $this->tenants->runForSite($m->siteId, function() use ($m) {
            $plan = $this->plans->get($m->planId);
            $assetPlan = $plan->assets[$m->assetId] ?? null;
            if (!$assetPlan) return;

            $finalUrl = $this->storage->fetchAndStore($assetPlan);
            $this->links->swapAssetReferences($m->planId, $m->assetId, $finalUrl);
        });
    }
}
