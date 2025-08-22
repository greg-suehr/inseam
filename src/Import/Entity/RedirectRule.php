<?php
namespace App\Import\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'redirect_rule')]
#[ORM\UniqueConstraint(name:'uniq_redirect_old', columns:['old_url'])]
class RedirectRule
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type:'bigint')]
    private ?int $id = null;

    #[ORM\Column(type:'bigint')]
    private int $planIdFk;

    #[ORM\Column(type:'text')]
    private string $oldUrl;

    #[ORM\Column(type:'text')]
    private string $newRoute;

    #[ORM\Column(type:'boolean')]
    private bool $applied = false;
}
