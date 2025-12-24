<?php
// src/Entity/RendezVous.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: 'App\Repository\RendezVousRepository')]
#[ORM\Table(name: 'rendez_vous')]
class RendezVous
{
    const STATUT_ATTENTE = 'en_attente';
    const STATUT_CONFIRME = 'confirme';
    const STATUT_ANNULE = 'annule';
    const STATUT_TERMINE = 'termine';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Patient', inversedBy: 'rendezVous')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Medecin', inversedBy: 'rendezVous')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Medecin $medecin = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateHeure = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $statut = self::STATUT_ATTENTE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'integer', options: ['default' => 30])]
    private int $duree = 30; // Durée en minutes

    #[ORM\OneToOne(targetEntity: 'App\Entity\Consultation', mappedBy: 'rendezVous')]
    private ?Consultation $consultation = null;



    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->statut = self::STATUT_ATTENTE;
        $this->createdAt = new \DateTimeImmutable();
        $this->duree = 30;
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): self
    {
        $this->patient = $patient;
        return $this;
    }

    public function getMedecin(): ?Medecin
    {
        return $this->medecin;
    }

    public function setMedecin(?Medecin $medecin): self
    {
        $this->medecin = $medecin;
        return $this;
    }

    public function getDateHeure(): ?\DateTimeInterface
    {
        return $this->dateHeure;
    }

    public function setDateHeure(\DateTimeInterface $dateHeure): self
    {
        $this->dateHeure = $dateHeure;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        if (!in_array($statut, [self::STATUT_ATTENTE, self::STATUT_CONFIRME, self::STATUT_ANNULE, self::STATUT_TERMINE])) {
            throw new \InvalidArgumentException("Statut invalide: $statut");
        }
        $this->statut = $statut;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): self
    {
        $this->motif = $motif;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getDuree(): int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): self
    {
        $this->duree = $duree;
        return $this;
    }



    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Méthodes utilitaires
    public function confirmer(): self
    {
        return $this->setStatut(self::STATUT_CONFIRME);
    }

    public function annuler(): self
    {
        return $this->setStatut(self::STATUT_ANNULE);
    }

    public function terminer(): self
    {
        return $this->setStatut(self::STATUT_TERMINE);
    }

    public function estConfirme(): bool
    {
        return $this->statut === self::STATUT_CONFIRME;
    }

    public function estAnnule(): bool
    {
        return $this->statut === self::STATUT_ANNULE;
    }

    public function estTermine(): bool
    {
        return $this->statut === self::STATUT_TERMINE;
    }

    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_ATTENTE;
    }

    public function estPasse(): bool
    {
        return $this->dateHeure < new \DateTime();
    }

    public function estAVenir(): bool
    {
        return $this->dateHeure > new \DateTime();
    }

    public function getDateFin(): \DateTimeInterface
    {
        return \DateTimeImmutable::createFromInterface($this->dateHeure)->modify("+{$this->duree} minutes");
    }

    // Méthode pour obtenir la date formatée
    public function getDateFormatee(): string
    {
        return $this->dateHeure->format('d/m/Y');
    }

    public function getHeureFormatee(): string
    {
        return $this->dateHeure->format('H:i');
    }

    public function getDateHeureFormatee(): string
    {
        return $this->dateHeure->format('d/m/Y à H:i');
    }
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }
    // Méthode pour vérifier si le RDV est aujourd'hui
    public function estAujourdhui(): bool
    {
        return $this->dateHeure->format('Y-m-d') === (new \DateTime())->format('Y-m-d');
    }

    public function __toString(): string
    {
        return sprintf(
            'RDV %s - %s avec Dr. %s',
            $this->dateHeure->format('d/m/Y H:i'),
            $this->patient?->getNomComplet() ?? 'Patient',
            $this->medecin?->getNomComplet() ?? 'Médecin'
        );
    }
}