<?php
namespace App\Import\Tenancy;

use Doctrine\ORM\EntityManagerInterface;

final class TenantContextAdaptor implements TenantContext
{
    public function __construct(private EntityManagerInterface $em) {}

    /** Run $fn with the connection's search_path set to site_<siteId>, then restore it. */
    public function runForSite(string|int $siteId, callable $fn): void
    {
        $conn = $this->em->getConnection();

        // Capture the full current search_path, not just current_schema().
        $prev = $conn->fetchOne('SHOW search_path');

        try {
            $schema = sprintf('site_%s', $siteId);
            $conn->executeStatement(sprintf('SET search_path TO "%s", public', $schema));
            $fn();
        } finally {
            // restore for long-running workers
            $conn->executeStatement(sprintf('SET search_path TO %s', $prev));
        }
    }
}
