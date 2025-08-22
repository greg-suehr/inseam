<?php
namespace App\Import\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Import\Message\FinalizeImportMessage;
use App\Import\Planning\PlanRepository;
use App\Import\Entity\ImportExecution;
use Doctrine\ORM\EntityManagerInterface;
use App\Import\Tenancy\TenantContext;

#[AsMessageHandler]
final class FinalizeImportHandler
{
    public function __construct(
        private TenantContext $tenants,
        private PlanRepository $plans,
        private EntityManagerInterface $em
    ) {}

    public function __invoke(FinalizeImportMessage $m): void
    {
        $this->tenants->runForSite($m->siteId, function() use ($m) {
            // Mark execution done, emit events, etc.
            // $exec = $this->em->find(ImportExecution::class, ...);
            // $exec->setStatus('done'); $this->em->flush();
        });
    }
}
