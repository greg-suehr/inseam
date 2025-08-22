<?php
namespace App\Import;

use App\Import\DTO\ImportSource;
use App\Import\DTO\ImportPolicy;
use App\Import\DTO\DiscoveryResult;
use App\Import\DTO\Planning\ImportPlan;
use App\Import\Execution\ImportExecution;

interface ImporterInterface
{
    public function getKey(): string; // "static","squarespace","wix"
    public function getCapabilities(): ImporterCapabilities;

    public function discover(ImportSource $source): DiscoveryResult;

    public function plan(DiscoveryResult $graph, ImportPolicy $policy): ImportPlan;

    /** Enqueue jobs and return execution record */
    public function execute(ImportPlan $plan): ImportExecution;
}
