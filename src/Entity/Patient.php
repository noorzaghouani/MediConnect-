<?php

namespace App\Entity;

use App\Repository\PatientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
class Patient extends User
{
    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: RendezVous::class)]
    private Collection $rendezVous;

    #[ORM\OneToOne(mappedBy: 'patient', targetEntity: DossierMedical::class, cascade: ['persist', 'remove'])]
    private ?DossierMedical $dossierMedical = null;



    public function __construct()
    {
        parent::__construct();
        $this->setRoles(['ROLE_PATIENT']);
        $this->rendezVous = new ArrayCollection();
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
            $rendezVous->setPatient($this);
        }

        return $this;
    }

    public function removeRendezVous(RendezVous $rendezVous): self
    {
        if ($this->rendezVous->removeElement($rendezVous)) {
            // set the owning side to null (unless already changed)
            if ($rendezVous->getPatient() === $this) {
                $rendezVous->setPatient(null);
            }
        }

        return $this;
    }


    public function getDossierMedical(): ?DossierMedical
    {
        return $this->dossierMedical;
    }

    public function setDossierMedical(DossierMedical $dossierMedical): self
    {
        // set the owning side of the relation if necessary
        if ($dossierMedical->getPatient() !== $this) {
            $dossierMedical->setPatient($this);
        }

        $this->dossierMedical = $dossierMedical;

        return $this;
    }
}