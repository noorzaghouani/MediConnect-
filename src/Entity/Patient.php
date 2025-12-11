<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
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
}