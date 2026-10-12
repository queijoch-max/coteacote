<?php

namespace App\Entity;

use App\Repository\MotInterditRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MotInterditRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_mot_interdit', fields: ['mot'])]
class MotInterdit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    private string $mot = '';

    #[ORM\Column]
    private bool $actif = true;

    public function getId(): ?int { return $this->id; }

    public function getMot(): string { return $this->mot; }
    public function setMot(string $mot): static { $this->mot = mb_strtolower(trim($mot)); return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }
}
