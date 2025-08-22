<?php
namespace App\Import\Tenancy;

interface TenantContext
{
    /** Ensure DB schema/scope is set for the site, then run the callback. */
    public function runForSite(string $siteId, callable $fn): void;
}
