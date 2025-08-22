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
}
