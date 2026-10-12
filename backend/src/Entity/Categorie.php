<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $libelle = '';

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    private ?Categorie $parent = null;

    /** @var Collection<int, Categorie> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $enfants;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column]
    private int $ordre = 0;

    public function __construct()
    {
        $this->enfants = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getLibelle(): string { return $this->libelle; }
    public function setLibelle(string $libelle): static { $this->libelle = trim($libelle); return $this; }

    public function getParent(): ?Categorie { return $this->parent; }
    public function setParent(?Categorie $parent): static { $this->parent = $parent; return $this; }

    /** @return Collection<int, Categorie> */
    public function getEnfants(): Collection { return $this->enfants; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }

    public function getOrdre(): int { return $this->ordre; }
    public function setOrdre(int $ordre): static { $this->ordre = $ordre; return $this; }
}
