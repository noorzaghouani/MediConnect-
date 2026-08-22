<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'consultations', targetEntity: DossierMedical::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?DossierMedical $dossierMedical = null;

    #[ORM\ManyToOne(inversedBy: 'consultations', targetEntity: Medecin::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Medecin $medecin = null;

    #[ORM\OneToOne(mappedBy: 'consultation', targetEntity: RendezVous::class)]
    private ?RendezVous $rendezVous = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "Le motif est obligatoire")]
    private ?string $motif = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "Le diagnostic est obligatoire")]
    private ?string $diagnostic = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $symptomes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $allergies = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $tension = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(
        min: 30,
        max: 45,
        notInRangeMessage: "La température doit être entre {{ min }}° et {{ max }}°C"
    )]
    private ?float $temperature = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 40,
        max: 200,
        notInRangeMessage: "La fréquence cardiaque doit être entre {{ min }} et {{ max }} bpm"
    )]
    private ?int $frequenceCardiaque = null;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMedecin(): ?Medecin
    {
        return $this->medecin;
    }

    public function setMedecin(?Medecin $medecin): self
    {
        $this->medecin = $medecin;
        return $this;
    }

    public function getRendezVous(): ?RendezVous
    {
        return $this->rendezVous;
    }

    public function setRendezVous(?RendezVous $rendezVous): self
    {
        // Unset previous relation if needed
        if ($rendezVous === null && $this->rendezVous !== null) {
            $this->rendezVous->setConsultation(null);
        }

        // Set new relation
        if ($rendezVous !== null && $rendezVous->getConsultation() !== $this) {
            $rendezVous->setConsultation($this);
        }

        $this->rendezVous = $rendezVous;
        return $this;
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

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): self
    {
        $this->motif = $motif;
        return $this;
    }

    public function getDiagnostic(): ?string
    {
        return $this->diagnostic;
    }

    public function setDiagnostic(string $diagnostic): self
    {
        $this->diagnostic = $diagnostic;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        $this->observations = $observations;
        return $this;
    }

    public function getSymptomes(): ?string
    {
        return $this->symptomes;
    }

    public function setSymptomes(?string $symptomes): self
    {
        $this->symptomes = $symptomes;
        return $this;
    }

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setAllergies(?string $allergies): self
    {
        $this->allergies = $allergies;
        return $this;
    }

    public function getTension(): ?string
    {
        return $this->tension;
    }

    public function setTension(?string $tension): self
    {
        $this->tension = $tension;
        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }

    public function getFrequenceCardiaque(): ?int
    {
        return $this->frequenceCardiaque;
    }

    public function setFrequenceCardiaque(?int $frequenceCardiaque): self
    {
        $this->frequenceCardiaque = $frequenceCardiaque;
        return $this;
    }
}
