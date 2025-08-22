<?php // src/Import/Entity/ImportDiscovery.php
namespace App\Import\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'import_discovery')]
#[ORM\UniqueConstraint(name:'uniq_discovery_graph', columns:['graph_id'])]
class ImportDiscovery
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type:'bigint')]
    private ?int $id = null;

    #[ORM\Column(type:'bigint')]
    private int $sessionId;

    #[ORM\Column(type:'string', length:64)]
    private string $graphId;

    #[ORM\Column(type:'json')]
    private array $graphJson; // nodes/edges with blob refs

    #[ORM\Column(type:'json', nullable:true)]
    private ?array $stats = null; // counts, sizes, durations

    #[ORM\Column(type:'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(int $sessionId, string $graphId, array $graphJson, ?array $stats = null)
    {
        $this->sessionId = $sessionId;
        $this->graphId = $graphId;
        $this->graphJson = $graphJson;
        $this->stats = $stats;
        $this->createdAt = new \DateTimeImmutable();
    }
}
