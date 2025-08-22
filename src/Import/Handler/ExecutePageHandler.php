<?php
namespace App\Import\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Import\Message\ExecutePageMessage;
use App\Import\Planning\PageWriter;
use App\Import\Planning\RoutingEngine;
use App\Import\Planning\PlanRepository;
use App\Import\Tenancy\TenantContext;

#[AsMessageHandler]
final class ExecutePageHandler
{
    public function __construct(
        private TenantContext $tenants,
        private PlanRepository $plans,
        private PageWriter $writer,
        private RoutingEngine $routes
    ) {}

    public function __invoke(ExecutePageMessage $m): void
    {
        $this->tenants->runForSite($m->siteId, function() use ($m) {
            $plan = $this->plans->get($m->planId);
            $pagePlan = $plan->pages[$m->pageId] ?? null;
            if (!$pagePlan) return;

            $route = $this->routes->resolve($plan->routes[$m->pageId]);
            $this->writer->upsert($route, $pagePlan, $plan->styles, provenance: [
                'planId' => $plan->planId,
                'sourceUrl' => $pagePlan->sourceUrl,
                'sourceHash' => $pagePlan->sourceHash,
            ]);
        });
    }
}
