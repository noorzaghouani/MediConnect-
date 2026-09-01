<?php

namespace App\Tests\Unit\Entity;

use App\Entity\RendezVous;
use App\Entity\Patient;
use App\Entity\Medecin;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires — Entité RendezVous
 *
 * Couvre la logique métier pure : transitions de statut,
 * méthodes utilitaires de date, et calcul de date de fin.
 * Aucune base de données requise.
 */
class RendezVousTest extends TestCase
{
    private RendezVous $rdv;

    protected function setUp(): void
    {
        $this->rdv = new RendezVous();
    }

    // -------------------------------------------------------------------------
    // Tests — Statut initial
    // -------------------------------------------------------------------------

    /**
     * @testdox Un nouveau RendezVous a le statut "en_attente" par défaut
     */
    public function testStatutInitialEstEnAttente(): void
    {
        $this->assertSame(RendezVous::STATUT_ATTENTE, $this->rdv->getStatut());
    }

    /**
     * @testdox Un nouveau RendezVous est en attente (estEnAttente = true)
     */
    public function testEstEnAttenteRetourneTrueParDefaut(): void
    {
        $this->assertTrue($this->rdv->estEnAttente());
    }

    /**
     * @testdox Un nouveau RendezVous n'est pas confirmé
     */
    public function testEstConfirmeFalseParDefaut(): void
    {
        $this->assertFalse($this->rdv->estConfirme());
    }

    /**
     * @testdox Un nouveau RendezVous n'est pas annulé
     */
    public function testEstAnnuleFalseParDefaut(): void
    {
        $this->assertFalse($this->rdv->estAnnule());
    }

    /**
     * @testdox Un nouveau RendezVous n'est pas terminé
     */
    public function testEstTermineFalseParDefaut(): void
    {
        $this->assertFalse($this->rdv->estTermine());
    }

    // -------------------------------------------------------------------------
    // Tests — Durée par défaut
    // -------------------------------------------------------------------------

    /**
     * @testdox La durée par défaut d'un RendezVous est 30 minutes
     */
    public function testDureeParDefautEst30Minutes(): void
    {
        $this->assertSame(30, $this->rdv->getDuree());
    }

    // -------------------------------------------------------------------------
    // Tests — Transitions de statut
    // -------------------------------------------------------------------------

    /**
     * @testdox confirmer() passe le statut à "confirme"
     */
    public function testConfirmerPasseLeStatutAConfirme(): void
    {
        $this->rdv->confirmer();

        $this->assertSame(RendezVous::STATUT_CONFIRME, $this->rdv->getStatut());
        $this->assertTrue($this->rdv->estConfirme());
        $this->assertFalse($this->rdv->estEnAttente());
    }

    /**
     * @testdox annuler() passe le statut à "annule"
     */
    public function testAnnulerPasseLeStatutAAnnule(): void
    {
        $this->rdv->annuler();

        $this->assertSame(RendezVous::STATUT_ANNULE, $this->rdv->getStatut());
        $this->assertTrue($this->rdv->estAnnule());
        $this->assertFalse($this->rdv->estEnAttente());
    }

    /**
     * @testdox terminer() passe le statut à "termine"
     */
    public function testTerminerPasseLeStatutATermine(): void
    {
        $this->rdv->terminer();

        $this->assertSame(RendezVous::STATUT_TERMINE, $this->rdv->getStatut());
        $this->assertTrue($this->rdv->estTermine());
    }

    /**
     * @testdox setStatut() avec une valeur invalide lève InvalidArgumentException
     */
    public function testSetStatutInvalideLèveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Statut invalide/');

        $this->rdv->setStatut('statut_inexistant');
    }

    /**
     * @testdox setStatut() accepte les 4 valeurs valides sans exception
     */
    public function testSetStatutAccepteLesValeursValides(): void
    {
        $statutsValides = [
            RendezVous::STATUT_ATTENTE,
            RendezVous::STATUT_CONFIRME,
            RendezVous::STATUT_ANNULE,
            RendezVous::STATUT_TERMINE,
        ];

        foreach ($statutsValides as $statut) {
            $this->rdv->setStatut($statut);
            $this->assertSame($statut, $this->rdv->getStatut());
        }
    }

    // -------------------------------------------------------------------------
    // Tests — Méthodes de date
    // -------------------------------------------------------------------------

    /**
     * @testdox getDateFin() retourne null si dateHeure n'est pas définie
     */
    public function testGetDateFinRetourneNullSansDateHeure(): void
    {
        $this->assertNull($this->rdv->getDateFin());
    }

    /**
     * @testdox getDateFin() = dateHeure + durée en minutes
     */
    public function testGetDateFinEgaleDateHeuresPlusDuree(): void
    {
        $dateDebut = new \DateTime('2026-09-01 10:00:00');
        $this->rdv->setDateHeure($dateDebut);
        $this->rdv->setDuree(30);

        $dateFin = $this->rdv->getDateFin();

        $this->assertNotNull($dateFin);
        $this->assertSame('2026-09-01 10:30:00', $dateFin->format('Y-m-d H:i:s'));
    }

    /**
     * @testdox getDateFin() respecte une durée personnalisée de 20 minutes
     */
    public function testGetDateFinAvecDureePersonnalisee(): void
    {
        $this->rdv->setDateHeure(new \DateTime('2026-09-01 14:00:00'));
        $this->rdv->setDuree(20);

        $dateFin = $this->rdv->getDateFin();

        $this->assertSame('2026-09-01 14:20:00', $dateFin->format('Y-m-d H:i:s'));
    }

    /**
     * @testdox estPasse() retourne true si dateHeure est dans le passé
     */
    public function testEstPasseRetourneTrueSiDateDansLePasse(): void
    {
        $this->rdv->setDateHeure(new \DateTime('-1 day'));

        $this->assertTrue($this->rdv->estPasse());
        $this->assertFalse($this->rdv->estAVenir());
    }

    /**
     * @testdox estAVenir() retourne true si dateHeure est dans le futur
     */
    public function testEstAVenirRetourneTrueSiDateDansLeFutur(): void
    {
        $this->rdv->setDateHeure(new \DateTime('+1 day'));

        $this->assertTrue($this->rdv->estAVenir());
        $this->assertFalse($this->rdv->estPasse());
    }

    /**
     * @testdox estPasse() retourne false si aucune date n'est définie
     */
    public function testEstPasseRetourneFalseSansDate(): void
    {
        $this->assertFalse($this->rdv->estPasse());
    }

    /**
     * @testdox estAVenir() retourne false si aucune date n'est définie
     */
    public function testEstAVenirRetourneFalseSansDate(): void
    {
        $this->assertFalse($this->rdv->estAVenir());
    }

    // -------------------------------------------------------------------------
    // Tests — Formatage de dates
    // -------------------------------------------------------------------------

    /**
     * @testdox getDateFormatee() retourne la date au format d/m/Y
     */
    public function testGetDateFormateeRetourneLeFormatCorrect(): void
    {
        $this->rdv->setDateHeure(new \DateTime('2026-09-01 10:30:00'));

        $this->assertSame('01/09/2026', $this->rdv->getDateFormatee());
    }

    /**
     * @testdox getHeureFormatee() retourne l'heure au format H:i
     */
    public function testGetHeureFormateeRetourneLeFormatCorrect(): void
    {
        $this->rdv->setDateHeure(new \DateTime('2026-09-01 14:30:00'));

        $this->assertSame('14:30', $this->rdv->getHeureFormatee());
    }

    /**
     * @testdox getDateFormatee() retourne une chaîne vide si pas de date
     */
    public function testGetDateFormateeRetourneChaineVideSansDate(): void
    {
        $this->assertSame('', $this->rdv->getDateFormatee());
    }

    // -------------------------------------------------------------------------
    // Tests — createdAt automatique
    // -------------------------------------------------------------------------

    /**
     * @testdox Un nouveau RendezVous a createdAt défini automatiquement
     */
    public function testCreatedAtEstDefiniAutomatiquement(): void
    {
        $this->assertNotNull($this->rdv->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->rdv->getCreatedAt());
    }

    /**
     * @testdox updatedAt est null à la création
     */
    public function testUpdatedAtEstNullALaCreation(): void
    {
        $this->assertNull($this->rdv->getUpdatedAt());
    }
}
