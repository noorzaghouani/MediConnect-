<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Medecin extends User
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $diplome = null;

    public function __construct()
    {
        parent::__construct();
        $this->setRoles(['ROLE_MEDECIN']);
    }

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialite = null;

    public function getDiplome(): ?string
    {
        return $this->diplome;
    }

    public function setDiplome(?string $diplome): static
    {
        $this->diplome = $diplome;

        return $this;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): static
    {
        $this->specialite = $specialite;

        return $this;
    }
}