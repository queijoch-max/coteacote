<?php

namespace App\Entity;

use App\Repository\CommuneRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommuneRepository::class)]
class Commune
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $nom = '';

    #[ORM\Column(length: 5)]
    private string $codePostal = '';

    #[ORM\Column(length: 5)]
    private string $codeInsee = '';

    #[ORM\Column(length: 180)]
    private string $emailContact = '';

    #[ORM\Column(length: 20)]
    private string $telephoneContact = '';

    public function getId(): ?int { return $this->id; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getCodePostal(): string { return $this->codePostal; }
    public function setCodePostal(string $codePostal): static { $this->codePostal = $codePostal; return $this; }

    public function getCodeInsee(): string { return $this->codeInsee; }
    public function setCodeInsee(string $codeInsee): static { $this->codeInsee = $codeInsee; return $this; }

    public function getEmailContact(): string { return $this->emailContact; }
    public function setEmailContact(string $emailContact): static { $this->emailContact = $emailContact; return $this; }

    public function getTelephoneContact(): string { return $this->telephoneContact; }
    public function setTelephoneContact(string $telephoneContact): static { $this->telephoneContact = $telephoneContact; return $this; }
}
