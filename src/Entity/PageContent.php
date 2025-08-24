<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'page_content')]
#[ORM\UniqueConstraint(name:'uniq_plan_route', columns:['plan_id','route'])]
class PageContent
{
  #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type:'bigint')]
  private ?int $id = null;
  
  #[ORM\Column(name:'plan_id', type:'string', length:64)]
  private string $planId;
  
  #[ORM\Column(type:'string', length:2048)]
  private string $route;
  
  #[ORM\Column(type:'text')]
  private string $content;
  
  #[ORM\Column(type:'json', nullable:true)]
  private ?array $provenance = null;
  
  #[ORM\Column(type:'datetime_immutable')]
  private \DateTimeImmutable $createdAt;
  
  #[ORM\Column(type:'datetime_immutable')]
  private \DateTimeImmutable $updatedAt;
  
  public function __construct(string $planId, string $route, string $content, ?array $prov = null)
  {
      $this->planId = $planId;
      $this->route = $route;
      $this->content = $content;
      $this->provenance = $prov;
      $this->createdAt = new \DateTimeImmutable();
      $this->updatedAt = new \DateTimeImmutable();
    }
  
  public function getId(): ?int { return $this->id; }
  public function getPlanId(): string { return $this->planId; }
  public function getRoute(): string { return $this->route; }
  public function getContent(): string { return $this->content; }
  public function setContent(string $html): void { $this->content = $html; $this->updatedAt = new \DateTimeImmutable(); }
  public function setProvenance(?array $p): void { $this->provenance = $p; $this->updatedAt = new \DateTimeImmutable(); }
}
