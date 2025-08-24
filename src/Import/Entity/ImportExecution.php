<?php
namespace App\Import\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'import_execution')]
class ImportExecution
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type:'bigint')]
    private ?int $id = null;

    #[ORM\Column(type:'bigint')]
    private int $planIdFk;

    #[ORM\Column(length: 32)]
    private string $status = 'queued'; // queued|running|done|failed

    #[ORM\Column(type:'json', nullable:true)]
    private ?array $progress = null; // write-ahead markers per page/asset

    #[ORM\Column(type:'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(int $planIdFk)
    {
        $this->planIdFk = $planIdFk;
        $this->createdAt = new \DateTimeImmutable();
    }

  public function getId(): ?int { return $this->id; }
  public function getPlanIdFk(): int { return $this->planIdFk; }
  public function getStatus(): string { return $this->status; }
  public function getProgress(): ?array { return $this->progress; }
  public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
  public function getStartedAt(): ?\DateTimeImmutable { return $this->startedAt; }
  public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
  public function getErrorMessage(): ?string { return $this->errorMessage; }

  public function setStatus(string $status): self {
        $this->status = $status;
        
        if ($status === 'running' && !$this->startedAt) {
          $this->startedAt = new \DateTimeImmutable();
        }
        
        if (in_array($status, ['done', 'failed']) && !$this->completedAt) {
          $this->completedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }
  
  public function setProgress(?array $progress): self {
        $this->progress = $progress;
        return $this;
    }
  
  public function setErrorMessage(?string $errorMessage): self {
        $this->errorMessage = $errorMessage;
        return $this;
    }
}
