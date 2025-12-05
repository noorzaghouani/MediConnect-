<?php
// src/Entity/Consultation.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: RendezVous::class, inversedBy: 'consultation')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RendezVous $rendezVous = null;

    #[ORM\ManyToOne(targetEntity: DossierMedical::class, inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DossierMedical $dossierMedical = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $diagnostic = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateConsultation = null;

    #[ORM\OneToOne(targetEntity: Ordonnance::class, mappedBy: 'consultation')]
    private ?Ordonnance $ordonnance = null;

    #[ORM\Column(type: 'integer')]
    private int $duree = 30; // Durée en minutes

    #[ORM\Column(type: 'string', length: 20)]
    private string $type = 'visioconference';

    public function __construct()
    {
        $this->dateConsultation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRendezVous(): ?RendezVous
    {
        return $this->rendezVous;
    }

    public function setRendezVous(?RendezVous $rendezVous): self
    {
        $this->rendezVous = $rendezVous;
        return $this;
    }

    public function getDossierMedical(): ?DossierMedical
    {
        return $this->dossierMedical;
    }

    public function setDossierMedical(?DossierMedical $dossierMedical): self
    {
        $this->dossierMedical = $dossierMedical;
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

    public function getDiagnostic(): ?string
    {
        return $this->diagnostic;
    }

    public function setDiagnostic(?string $diagnostic): self
    {
        $this->diagnostic = $diagnostic;
        return $this;
    }

    public function getDateConsultation(): ?\DateTimeInterface
    {
        return $this->dateConsultation;
    }

    public function setDateConsultation(\DateTimeInterface $dateConsultation): self
    {
        $this->dateConsultation = $dateConsultation;
        return $this;
    }

    public function getOrdonnance(): ?Ordonnance
    {
        return $this->ordonnance;
    }

    public function setOrdonnance(?Ordonnance $ordonnance): self
    {
        $this->ordonnance = $ordonnance;
        if ($ordonnance && $ordonnance->getConsultation() !== $this) {
            $ordonnance->setConsultation($this);
        }
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }
}