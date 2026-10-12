<?php

namespace App\Entity;

use App\Enum\MotifSignalement;
use App\Enum\StatutSignalement;
use App\Repository\SignalementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignalementRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_signalement', fields: ['publication', 'user'])]
class Signalement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, enumType: MotifSignalement::class)]
    private MotifSignalement $motif;

    #[ORM\Column(length: 12, enumType: StatutSignalement::class)]
    private StatutSignalement $statut = StatutSignalement::EnAttente;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Publication $publication;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function __construct(Publication $publication, User $user, MotifSignalement $motif)
    {
        $this->publication = $publication;
        $this->user = $user;
        $this->motif = $motif;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getMotif(): MotifSignalement { return $this->motif; }
    public function getStatut(): StatutSignalement { return $this->statut; }
    public function setStatut(StatutSignalement $statut): static { $this->statut = $statut; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getPublication(): Publication { return $this->publication; }
    public function getUser(): User { return $this->user; }
}
