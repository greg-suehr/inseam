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

    #[ORM\Column(type:'json')]
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

  public function getId(): ?int { return $this->id; }
  public function getSessionId(): int { return $this->sessionId; }
  public function getPlanId(): string { return $this->planId; }
  public function getChecksum(): string { return $this->checksum; }
  public function getPlanJson(): array { return $this->planJson; }
  public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

  public function setPlanJson(array $planJson): self {
        $this->planJson = $planJson;
        $this->checksum = hash('sha256', json_encode($planJson));
        return $this;
    }
}
