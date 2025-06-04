<?php

namespace App\Entity;

use App\Repository\BioLinkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BioLinkRepository::class)]
class BioLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bioLinks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Admin $profile = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $hyperlink = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfile(): ?Admin
    {
        return $this->profile;
    }

    public function setProfile(?Admin $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getHyperlink(): ?string
    {
        return $this->hyperlink;
    }

    public function setHyperlink(string $hyperlink): static
    {
        $this->hyperlink = $hyperlink;

        return $this;
    }
}
