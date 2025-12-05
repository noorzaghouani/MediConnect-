<?php
// src/Entity/DossierMedical.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierMedicalRepository::class)]
class DossierMedical
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Patient::class, inversedBy: 'dossierMedical')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Patient $patient = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $antecedents = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $allergies = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $traitements = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaires = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $groupeSanguin = null;

    #[ORM\OneToMany(targetEntity: Consultation::class, mappedBy: 'dossierMedical')]
    private Collection $consultations;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateModification = null;

    public function __construct()
    {
        $this->consultations = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->dateModification = new \DateTime();
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

    public function getAntecedents(): ?string
    {
        return $this->antecedents;
    }

    public function setAntecedents(?string $antecedents): self
    {
        $this->antecedents = $antecedents;
        $this->dateModification = new \DateTime();
        return $this;
    }

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setAllergies(?string $allergies): self
    {
        $this->allergies = $allergies;
        $this->dateModification = new \DateTime();
        return $this;
    }

    public function getTraitements(): ?string
    {
        return $this->traitements;
    }

    public function setTraitements(?string $traitements): self
    {
        $this->traitements = $traitements;
        $this->dateModification = new \DateTime();
        return $this;
    }

    public function getCommentaires(): ?string
    {
        return $this->commentaires;
    }

    public function setCommentaires(?string $commentaires): self
    {
        $this->commentaires = $commentaires;
        $this->dateModification = new \DateTime();
        return $this;
    }

    public function getGroupeSanguin(): ?string
    {
        return $this->groupeSanguin;
    }

    public function setGroupeSanguin(?string $groupeSanguin): self
    {
        $this->groupeSanguin = $groupeSanguin;
        $this->dateModification = new \DateTime();
        return $this;
    }

    /**
     * @return Collection<int, Consultation>
     */
    public function getConsultations(): Collection
    {
        return $this->consultations;
    }

    public function addConsultation(Consultation $consultation): self
    {
        if (!$this->consultations->contains($consultation)) {
            $this->consultations[] = $consultation;
            $consultation->setDossierMedical($this);
        }

        $this->dateModification = new \DateTime();
        return $this;
    }

    public function removeConsultation(Consultation $consultation): self
    {
        if ($this->consultations->removeElement($consultation)) {
            // set the owning side to null (unless already changed)
            if ($consultation->getDossierMedical() === $this) {
                $consultation->setDossierMedical(null);
            }
        }

        $this->dateModification = new \DateTime();
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    /**
     * Retourne les consultations triées par date (plus récentes en premier)
     * 
     * @return Consultation[]
     */
    public function getConsultationsTriees(): array
    {
        $consultations = $this->consultations->toArray();
        usort($consultations, function($a, $b) {
            return $b->getDateConsultation() <=> $a->getDateConsultation();
        });
        return $consultations;
    }

    /**
     * Retourne la dernière consultation
     */
    public function getDerniereConsultation(): ?Consultation
    {
        $consultations = $this->getConsultationsTriees();
        return $consultations[0] ?? null;
    }

    /**
     * Retourne le nombre total de consultations
     */
    public function getNombreConsultations(): int
    {
        return $this->consultations->count();
    }
}