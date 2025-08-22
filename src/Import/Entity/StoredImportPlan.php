<?php
namespace App\Import\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stored_import_plan')]
#[ORM\UniqueConstraint(name:'uniq_plan_checksum', columns:['checksum'])]
class StoredImportPlan
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type:'bigint')]
    private ?int $id = null;

    #[ORM\Column(type:'bigint')]
    private int $sessionId;

    #[ORM\Column(type:'string', length:64)]
    private string $planId;

    #[ORM\Column(type:'string', length:64)]
    private string $checksum;

    #[ORM\Column(type:'json')] // Postgres jsonb if you prefer via columnDefinition
    private array $planJson;

    #[ORM\Column(type:'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(int $sessionId, string $planId, string $checksum, array $planJson)
    {
        $this->sessionId = $sessionId;
        $this->planId = $planId;
        $this->checksum = $checksum;
        $this->planJson = $planJson;
        $this->createdAt = new \DateTimeImmutable();
    }
}
