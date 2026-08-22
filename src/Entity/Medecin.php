<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use App\Repository\MedecinRepository;

#[ORM\Entity(repositoryClass: MedecinRepository::class)]
class Medecin extends User
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $diplome = null;

    /**
     * @var Collection<int, Disponibilite>
     */
    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: Disponibilite::class, cascade: ['remove'])]
    private Collection $disponibilites;

    /**
     * @var Collection<int, RendezVous>
     */
    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: RendezVous::class, cascade: ['remove'])]
    private Collection $rendezVous;

    /**
     * @var Collection<int, Consultation>
     */
    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: Consultation::class, cascade: ['remove'])]
    private Collection $consultations;

    #[ORM\ManyToOne(targetEntity: Speciality::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Speciality $specialite = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $estVerifie = false;

    public function __construct()
    {
        parent::__construct();
        $this->setRoles(['ROLE_MEDECIN']);
        $this->disponibilites = new ArrayCollection();
        $this->rendezVous = new ArrayCollection();
        $this->consultations = new ArrayCollection();
    }

    public function getDiplome(): ?string
    {
        return $this->diplome;
    }

    public function setDiplome(?string $diplome): static
    {
        $this->diplome = $diplome;

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
            $this->consultations->add($consultation);
            $consultation->setMedecin($this);
        }

        return $this;
    }

    public function removeConsultation(Consultation $consultation): self
    {
        if ($this->consultations->removeElement($consultation)) {
            if ($consultation->getMedecin() === $this) {
                $consultation->setMedecin(null);
            }
        }

        return $this;
    }

    public function getSpecialite(): ?Speciality
    {
        return $this->specialite;
    }

    public function setSpecialite(?Speciality $specialite): static
    {
        $this->specialite = $specialite;

        return $this;
    }

    public function isEstVerifie(): bool
    {
        return $this->estVerifie;
    }

    public function setEstVerifie(bool $estVerifie): static
    {
        $this->estVerifie = $estVerifie;

        return $this;
    }

    /**
     * @return Collection<int, Disponibilite>
     */
    public function getDisponibilites(): Collection
    {
        return $this->disponibilites;
    }

    public function addDisponibilite(Disponibilite $disponibilite): self
    {
        if (!$this->disponibilites->contains($disponibilite)) {
            $this->disponibilites->add($disponibilite);
            $disponibilite->setMedecin($this);
        }

        return $this;
    }

    public function removeDisponibilite(Disponibilite $disponibilite): self
    {
        if ($this->disponibilites->removeElement($disponibilite)) {
            if ($disponibilite->getMedecin() === $this) {
                $disponibilite->setMedecin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RendezVous>
     */
    public function getRendezVous(): Collection
    {
        return $this->rendezVous;
    }

    public function addRendezVous(RendezVous $rendezVous): self
    {
        if (!$this->rendezVous->contains($rendezVous)) {
            $this->rendezVous->add($rendezVous);
            $rendezVous->setMedecin($this);
        }

        return $this;
    }

    public function removeRendezVous(RendezVous $rendezVous): self
    {
        if ($this->rendezVous->removeElement($rendezVous)) {
            if ($rendezVous->getMedecin() === $this) {
                $rendezVous->setMedecin(null);
            }
        }

        return $this;
    }
}