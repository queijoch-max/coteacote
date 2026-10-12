<?php

namespace App\Entity;

use App\Repository\JournalActionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JournalActionRepository::class)]
class JournalAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    private string $action;

    #[ORM\Column(length: 30)]
    private string $cible;

    #[ORM\Column(nullable: true)]
    private ?int $cibleId;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    public function __construct(?User $user, string $action, string $cible, ?int $cibleId)
    {
        $this->user = $user;
        $this->action = $action;
        $this->cible = $cible;
        $this->cibleId = $cibleId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getAction(): string { return $this->action; }
    public function getCible(): string { return $this->cible; }
    public function getCibleId(): ?int { return $this->cibleId; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $d): static { $this->createdAt = $d; return $this; }
    public function getUser(): ?User { return $this->user; }
}
