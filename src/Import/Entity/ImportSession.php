<?php
namespace App\Import\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'import_session')]
class ImportSession
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type:'bigint')]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $siteId;

    #[ORM\Column(length: 32)]
    private string $platform; // "static","squarespace","wix"

    #[ORM\Column(type:'text')]
    private string $source; // entry URL or file path

    #[ORM\Column(length: 32)]
    private string $status = 'created'; // created|planned|running|done|failed

    #[ORM\Column(type:'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

  public function __construct(string $siteId, string $platform, string $source)
  {
      $this->siteId = $siteId;
      $this->platform = $platform;
      $this->source = $source;
      $this->createdAt = new \DateTimeImmutable();
        }

  public function getId(): ?int { return $this->id; }
  public function getSiteId(): string { return $this->siteId; }
  public function getPlatform(): string { return $this->platform; }
  public function getSource(): string { return $this->source; }
  public function getStatus(): string { return $this->status; }
  public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
  public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

  public function setStatus(string $status): self {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
  
  public function setSiteId(string $siteId): self {
        $this->siteId = $siteId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }    
}
