<?php
namespace App\Import\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'compat_stylesheet')]
class CompatStylesheet
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type:'bigint')]
    private ?int $id = null;

    #[ORM\Column(type:'bigint')]
    private int $planIdFk;

    #[ORM\Column(length: 128)]
    private string $scopeClass; // e.g. compat-<hash>

    #[ORM\Column(type:'text')]
    private string $cssText;
}
