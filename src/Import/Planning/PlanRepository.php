<?php
namespace App\Import\Planning;

use App\Import\Entity\StoredImportPlan;
use App\Import\DTO\Planning\ImportPlan;
use Doctrine\ORM\EntityManagerInterface;

final class PlanRepository
{
    public function __construct(private EntityManagerInterface $em) {}

    public function get(string $planId): StoredImportPlan
    {
        $row = $this->em->getRepository(StoredImportPlan::class)
                        ->findOneBy(['planId' => $planId]);
        if (!$row) throw new \RuntimeException("Plan not found: $planId");
        $data = $row->getPlanJson(); // add a getter on the entity

        // hydrate your DTOs from $data; keep it minimal to start:
        return \App\Import\Util\PlanHydrator::fromArray($data);
    }
}
