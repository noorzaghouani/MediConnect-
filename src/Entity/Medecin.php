<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Medecin extends User
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $diplome = null;

    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: Disponibilite::class)]
    private Collection $disponibilites;

    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: RendezVous::class)]
    private Collection $rendezVous;

    public function __construct()
    {
        parent::__construct();
        $this->setRoles(['ROLE_MEDECIN']);
        $this->disponibilites = new ArrayCollection();
        $this->rendezVous = new ArrayCollection();
    }

    #[ORM\ManyToOne(targetEntity: Speciality::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Speciality $specialite = null;

    public function getDiplome(): ?string
    {
        return $this->diplome;
    }

    public function setDiplome(?string $diplome): static
    {
        $this->diplome = $diplome;

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
}