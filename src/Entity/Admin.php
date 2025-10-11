<?php

namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken.')]
#[UniqueEntity(fields: ['email'], message: 'This email is already used.', ignoreNull: true)]
class Admin implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $last_login = null;

    private \DateTimeInterface $defaultLastLogin;

  #[ORM\Column(length: 255, nullable: true)]
    private ?string $bio = null;

    /**
     * @var Collection<int, BioTag>
     */
    #[ORM\OneToMany(targetEntity: BioTag::class, mappedBy: 'profile', orphanRemoval: true)]
    private Collection $bioTags;

    /**
     * @var Collection<int, Blurb>
     */
    #[ORM\OneToMany(targetEntity: Blurb::class, mappedBy: 'profile')]
    private Collection $blurbs;

    /**
     * @var Collection<int, BioLink>
     */
    #[ORM\OneToMany(targetEntity: BioLink::class, mappedBy: 'profile', orphanRemoval: true)]
    private Collection $bioLinks;

    /**
     * @var Collection<int, Site>
     */
    #[ORM\OneToMany(targetEntity: Site::class, mappedBy: 'owner')]
    private Collection $sites;

  public function __construct(?\DateTimeInterface $defaultLastLogin = null)
  {
      $this->defaultLastLogin = $defaultLastLogin ?? new \DateTime("2011-06-29");
      $this->bioTags = new ArrayCollection();
      $this->blurbs = new ArrayCollection();
      $this->bioLinks = new ArrayCollection();
      $this->sites = new ArrayCollection();
  }

  public function __toString()
  {
        return $this->username;
  }
  
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->last_login ?? $this->defaultLastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $last_login): static
    {
        $this->last_login = $last_login;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    /**
     * @return Collection<int, BioTag>
     */
    public function getBioTags(): Collection
    {
        return $this->bioTags;
    }

    public function addBioTag(BioTag $bioTag): static
    {
        if (!$this->bioTags->contains($bioTag)) {
            $this->bioTags->add($bioTag);
            $bioTag->setProfile($this);
        }

        return $this;
    }

    public function removeBioTag(BioTag $bioTag): static
    {
        if ($this->bioTags->removeElement($bioTag)) {
            // set the owning side to null (unless already changed)
            if ($bioTag->getProfile() === $this) {
                $bioTag->setProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Blurb>
     */
    public function getBlurbs(): Collection
    {
        return $this->blurbs;
    }

    public function addBlurb(Blurb $blurb): static
    {
        if (!$this->blurbs->contains($blurb)) {
            $this->blurbs->add($blurb);
            $blurb->setProfile($this);
        }

        return $this;
    }

    public function removeBlurb(Blurb $blurb): static
    {
        if ($this->blurbs->removeElement($blurb)) {
            // set the owning side to null (unless already changed)
            if ($blurb->getProfile() === $this) {
                $blurb->setProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BioLink>
     */
    public function getBioLinks(): Collection
    {
        return $this->bioLinks;
    }

    public function addBioLink(BioLink $bioLink): static
    {
        if (!$this->bioLinks->contains($bioLink)) {
            $this->bioLinks->add($bioLink);
            $bioLink->setProfile($this);
        }

        return $this;
    }

    public function removeBioLink(BioLink $bioLink): static
    {
        if ($this->bioLinks->removeElement($bioLink)) {
            // set the owning side to null (unless already changed)
            if ($bioLink->getProfile() === $this) {
                $bioLink->setProfile(null);
            }
        }

        return $this;
    }

  public function getSites(): array
  {
      return [];
  }

  public function addSite(Site $site): static
  {
      if (!$this->sites->contains($site)) {
          $this->sites->add($site);
          $site->setOwner($this);
      }

      return $this;
  }

  public function removeSite(Site $site): static
  {
      if ($this->sites->removeElement($site)) {
          // set the owning side to null (unless already changed)
          if ($site->getOwner() === $this) {
              $site->setOwner(null);
          }
      }

      return $this;
  }
}
