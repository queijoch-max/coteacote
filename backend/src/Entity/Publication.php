<?php

namespace App\Entity;

use App\Enum\NaturePublication;
use App\Enum\StatutPublication;
use App\Enum\TypePublication;
use App\Repository\PublicationRepository;
use App\Validator\ComplementAutorise;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Index(name: 'idx_publication_liste', columns: ['statut', 'expires_at'])]
class Publication
{
    public const DUREE_JOURS = 30;
    public const MAX_ACTIVES = 5;
    public const SEUIL_SIGNALEMENTS = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, enumType: TypePublication::class)]
    private TypePublication $type = TypePublication::Demande;

    #[ORM\Column(length: 10, enumType: NaturePublication::class)]
    private NaturePublication $nature = NaturePublication::Aide;

    #[ORM\Column(length: 30, nullable: true)]
    #[ComplementAutorise]
    private ?string $complement = null;

    #[ORM\Column(length: 12, enumType: StatutPublication::class)]
    private StatutPublication $statut = StatutPublication::Active;

    #[ORM\Column]
    private bool $publieeParMairie = false;

    /** Prénom et initiale de l'habitant sans Internet (publication faite par la mairie, EF-26). */
    #[ORM\Column(length: 70, nullable: true)]
    private ?string $beneficiaire = null;

    /** Rue de l'habitant sans Internet (publication faite par la mairie, EF-26). */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $rueBeneficiaire = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column]
    private bool $renouvelee = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $auteur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categorie $categorie = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $this->createdAt->modify('+'.self::DUREE_JOURS.' days');
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): TypePublication { return $this->type; }
    public function setType(TypePublication $type): static { $this->type = $type; return $this; }

    public function getNature(): NaturePublication { return $this->nature; }
    public function setNature(NaturePublication $nature): static { $this->nature = $nature; return $this; }

    public function getComplement(): ?string { return $this->complement; }
    public function setComplement(?string $complement): static
    {
        $complement = null === $complement ? null : trim($complement);
        $this->complement = '' === $complement ? null : $complement;
        return $this;
    }

    public function getStatut(): StatutPublication { return $this->statut; }
    public function setStatut(StatutPublication $statut): static { $this->statut = $statut; return $this; }

    public function isActive(): bool
    {
        return $this->statut === StatutPublication::Active && $this->expiresAt > new \DateTimeImmutable();
    }

    public function isPublieeParMairie(): bool { return $this->publieeParMairie; }
    public function setPublieeParMairie(bool $v): static { $this->publieeParMairie = $v; return $this; }

    public function getBeneficiaire(): ?string { return $this->beneficiaire; }
    public function setBeneficiaire(?string $v): static { $this->beneficiaire = $v; return $this; }

    public function getRueBeneficiaire(): ?string { return $this->rueBeneficiaire; }
    public function setRueBeneficiaire(?string $v): static { $this->rueBeneficiaire = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $d): static { $this->createdAt = $d; return $this; }

    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(\DateTimeImmutable $d): static { $this->expiresAt = $d; return $this; }

    public function isRenouvelee(): bool { return $this->renouvelee; }
    public function setRenouvelee(bool $v): static { $this->renouvelee = $v; return $this; }

    /** EF-12 : un seul renouvellement, pour 30 jours à partir d'aujourd'hui. */
    public function renouveler(): void
    {
        $this->renouvelee = true;
        $this->statut = StatutPublication::Active;
        $this->expiresAt = (new \DateTimeImmutable())->modify('+'.self::DUREE_JOURS.' days');
    }

    public function getAuteur(): ?User { return $this->auteur; }
    public function setAuteur(?User $auteur): static { $this->auteur = $auteur; return $this; }

    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function setCategorie(?Categorie $categorie): static { $this->categorie = $categorie; return $this; }
}
