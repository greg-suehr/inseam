<?php
namespace App\Import\Adapter;

use App\Import\ImporterInterface;
use App\Import\ImporterCapabilities;
use App\Import\DTO\{ImportSource, ImportPolicy, DiscoveryResult};
use App\Import\DTO\Planning\ImportPlan;
use App\Import\Execution\ImportExecution;

#[AutoconfigureTag('app.importer')]
class StaticHttpAdaptor implements ImporterInterface
{
    public function getKey(): string { return 'static'; }

    public function getCapabilities(): ImporterCapabilities
    {
        return new ImporterCapabilities(supportsHeadless:false, supportsExportFiles:false);
    }

    public function discover(ImportSource $source): DiscoveryResult
    {
        // crawl + build ContentGraph
        return new DiscoveryResult(graphId: bin2hex(random_bytes(8)), nodes: [], edges: []);
    }

    public function plan(DiscoveryResult $graph, ImportPolicy $policy): ImportPlan
    {
        // route + blocks + assets + styles + scripts
        return new ImportPlan(planId: bin2hex(random_bytes(16)), routes: [], pages: [], assets: [], styles: new \App\Import\DTO\Planning\StylePlan([],[],''), scripts: new \App\Import\DTO\Planning\ScriptPlan([],[]), redirects: new \App\Import\DTO\Planning\RedirectMap([]));
    }

    public function execute(ImportPlan $plan): ImportExecution
    {
        // dispatch messages; return execution record
        return new ImportExecution($plan->planId);
    }
}
