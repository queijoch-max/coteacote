<?php

namespace App\Entity;

use App\Enum\MotifRefus;
use App\Enum\StatutUser;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_PENDING = 'ROLE_PENDING';
    public const ROLE_HABITANT = 'ROLE_HABITANT';
    public const ROLE_MAIRIE = 'ROLE_MAIRIE';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    private string $prenom = '';

    #[ORM\Column(length: 60)]
    private string $nom = '';

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 120)]
    private string $rue = '';

    /** Un seul rôle par compte, stocké dans un tableau pour rester compatible avec Symfony. */
    #[ORM\Column]
    private array $roles = [self::ROLE_PENDING];

    #[ORM\Column(length: 20, enumType: StatutUser::class)]
    private StatutUser $statut = StatutUser::EnAttente;

    #[ORM\Column(length: 30, nullable: true, enumType: MotifRefus::class)]
    private ?MotifRefus $motifRefus = null;

    #[ORM\Column]
    private bool $partageTelephone = false;

    #[ORM\Column]
    private bool $partageEmail = true;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column]
    private bool $emailConfirme = false;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmationExpiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cguAccepteesAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** Date du dernier changement de statut (validation, refus, suspension). */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $statutChangeAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /** Statut à restaurer si l'habitant annule la suppression de son compte (RG-11). */
    #[ORM\Column(length: 20, nullable: true, enumType: StatutUser::class)]
    private ?StatutUser $statutAvantSuppression = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commune $commune = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = trim($prenom); return $this; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = trim($nom); return $this; }

    /** Affichage public : prénom + initiale du nom (EF-16). */
    public function getNomAffiche(): string
    {
        return trim($this->prenom.' '.mb_strtoupper(mb_substr($this->nom, 0, 1)).'.');
    }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = mb_strtolower(trim($email)); return $this; }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return array_values(array_unique($this->roles));
    }

    public function getRole(): string
    {
        return $this->roles[0] ?? self::ROLE_PENDING;
    }

    public function setRole(string $role): static
    {
        $this->roles = [$role];
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return \in_array($role, $this->roles, true);
    }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void
    {
    }

    public function getRue(): string { return $this->rue; }
    public function setRue(string $rue): static { $this->rue = trim($rue); return $this; }

    public function getStatut(): StatutUser { return $this->statut; }
    public function setStatut(StatutUser $statut): static
    {
        $this->statut = $statut;
        $this->statutChangeAt = new \DateTimeImmutable();
        return $this;
    }

    /** Seul un habitant validé (ni en attente, ni suspendu, ni supprimé) peut publier ou répondre. */
    public function peutAgir(): bool
    {
        return $this->statut === StatutUser::Valide && $this->hasRole(self::ROLE_HABITANT);
    }

    public function getMotifRefus(): ?MotifRefus { return $this->motifRefus; }
    public function setMotifRefus(?MotifRefus $motifRefus): static { $this->motifRefus = $motifRefus; return $this; }

    public function isPartageTelephone(): bool { return $this->partageTelephone; }
    public function setPartageTelephone(bool $v): static { $this->partageTelephone = $v; return $this; }

    public function isPartageEmail(): bool { return $this->partageEmail; }
    public function setPartageEmail(bool $v): static { $this->partageEmail = $v; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone ? trim($telephone) : null; return $this; }

    public function isEmailConfirme(): bool { return $this->emailConfirme; }
    public function setEmailConfirme(bool $v): static { $this->emailConfirme = $v; return $this; }

    public function getConfirmationToken(): ?string { return $this->confirmationToken; }
    public function setConfirmationToken(?string $t): static { $this->confirmationToken = $t; return $this; }

    public function getConfirmationExpiresAt(): ?\DateTimeImmutable { return $this->confirmationExpiresAt; }
    public function setConfirmationExpiresAt(?\DateTimeImmutable $d): static { $this->confirmationExpiresAt = $d; return $this; }

    public function getCguAccepteesAt(): ?\DateTimeImmutable { return $this->cguAccepteesAt; }
    public function setCguAccepteesAt(?\DateTimeImmutable $d): static { $this->cguAccepteesAt = $d; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $d): static { $this->createdAt = $d; return $this; }

    public function getStatutChangeAt(): ?\DateTimeImmutable { return $this->statutChangeAt; }
    public function setStatutChangeAt(?\DateTimeImmutable $d): static { $this->statutChangeAt = $d; return $this; }

    public function getDeletedAt(): ?\DateTimeImmutable { return $this->deletedAt; }
    public function setDeletedAt(?\DateTimeImmutable $d): static { $this->deletedAt = $d; return $this; }

    public function getStatutAvantSuppression(): ?StatutUser { return $this->statutAvantSuppression; }
    public function setStatutAvantSuppression(?StatutUser $s): static { $this->statutAvantSuppression = $s; return $this; }

    public function getCommune(): ?Commune { return $this->commune; }
    public function setCommune(?Commune $commune): static { $this->commune = $commune; return $this; }
}
