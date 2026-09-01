<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Medecin;
use App\Entity\Patient;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires — Entités User / Patient / Medecin
 *
 * Couvre les rôles par défaut, les méthodes utilitaires
 * et les propriétés initiales sans base de données.
 */
class UserTest extends TestCase
{
    // =========================================================================
    // Patient
    // =========================================================================

    /**
     * @testdox Un Patient nouvellement créé possède le rôle ROLE_PATIENT
     */
    public function testPatientARolePatient(): void
    {
        $patient = new Patient();

        $this->assertContains('ROLE_PATIENT', $patient->getRoles());
    }

    /**
     * @testdox getRoles() inclut toujours ROLE_USER (requis par Symfony)
     */
    public function testPatientARoleUserParDefaut(): void
    {
        $patient = new Patient();

        $this->assertContains('ROLE_USER', $patient->getRoles());
    }

    /**
     * @testdox Un Patient n'a pas le rôle ROLE_ADMIN
     */
    public function testPatientNaPasRoleAdmin(): void
    {
        $patient = new Patient();

        $this->assertNotContains('ROLE_ADMIN', $patient->getRoles());
    }

    /**
     * @testdox Un Patient n'a pas le rôle ROLE_MEDECIN
     */
    public function testPatientNaPasRoleMedecin(): void
    {
        $patient = new Patient();

        $this->assertNotContains('ROLE_MEDECIN', $patient->getRoles());
    }

    /**
     * @testdox getNomComplet() retourne "prénom nom" pour un Patient
     */
    public function testPatientGetNomCompletRetournePrenomNom(): void
    {
        $patient = new Patient();
        $patient->setNom('Dupont');
        $patient->setPrenom('Marie');

        $this->assertSame('Marie Dupont', $patient->getNomComplet());
    }

    /**
     * @testdox Un Patient a createdAt défini automatiquement à la création
     */
    public function testPatientCreatedAtEstDefiniALaCreation(): void
    {
        $patient = new Patient();

        $this->assertNotNull($patient->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $patient->getCreatedAt());
    }

    /**
     * @testdox Un Patient peut stocker et récupérer son email
     */
    public function testPatientSetterGetterEmail(): void
    {
        $patient = new Patient();
        $patient->setEmail('marie.dupont@example.com');

        $this->assertSame('marie.dupont@example.com', $patient->getEmail());
        $this->assertSame('marie.dupont@example.com', $patient->getUserIdentifier());
    }

    /**
     * @testdox Un Patient peut stocker et récupérer son téléphone
     */
    public function testPatientSetterGetterTelephone(): void
    {
        $patient = new Patient();
        $patient->setTelephone('+33612345678');

        $this->assertSame('+33612345678', $patient->getTelephone());
    }

    /**
     * @testdox Un Patient n'a pas de dossier médical à la création
     */
    public function testPatientNaPasDeDossierMedicalInitialement(): void
    {
        $patient = new Patient();

        $this->assertNull($patient->getDossierMedical());
    }

    /**
     * @testdox La collection de rendez-vous d'un Patient est vide à la création
     */
    public function testPatientRendezVousEstVideInitialement(): void
    {
        $patient = new Patient();

        $this->assertCount(0, $patient->getRendezVous());
    }

    // =========================================================================
    // Médecin
    // =========================================================================

    /**
     * @testdox Un Médecin nouvellement créé possède le rôle ROLE_MEDECIN
     */
    public function testMedecinARoleMedecin(): void
    {
        $medecin = new Medecin();

        $this->assertContains('ROLE_MEDECIN', $medecin->getRoles());
    }

    /**
     * @testdox Un Médecin possède aussi ROLE_USER par défaut
     */
    public function testMedecinARoleUserParDefaut(): void
    {
        $medecin = new Medecin();

        $this->assertContains('ROLE_USER', $medecin->getRoles());
    }

    /**
     * @testdox Un Médecin n'a pas ROLE_PATIENT
     */
    public function testMedecinNaPasRolePatient(): void
    {
        $medecin = new Medecin();

        $this->assertNotContains('ROLE_PATIENT', $medecin->getRoles());
    }

    /**
     * @testdox Un Médecin n'est pas vérifié par défaut (estVerifie = false)
     */
    public function testMedecinNonVerifieParDefaut(): void
    {
        $medecin = new Medecin();

        $this->assertFalse($medecin->isEstVerifie());
    }

    /**
     * @testdox setEstVerifie(true) marque le médecin comme vérifié
     */
    public function testMedecinPeutEtreVerifie(): void
    {
        $medecin = new Medecin();
        $medecin->setEstVerifie(true);

        $this->assertTrue($medecin->isEstVerifie());
    }

    /**
     * @testdox Un Médecin n'a pas de diplôme à la création
     */
    public function testMedecinNaPasDeDiplomeInitialement(): void
    {
        $medecin = new Medecin();

        $this->assertNull($medecin->getDiplome());
    }

    /**
     * @testdox Un Médecin peut stocker un nom de fichier de diplôme
     */
    public function testMedecinPeutSetterSonDiplome(): void
    {
        $medecin = new Medecin();
        $medecin->setDiplome('abc123.pdf');

        $this->assertSame('abc123.pdf', $medecin->getDiplome());
    }

    /**
     * @testdox Un Médecin n'a pas de spécialité par défaut
     */
    public function testMedecinNaPasDeSpecialiteParDefaut(): void
    {
        $medecin = new Medecin();

        $this->assertNull($medecin->getSpecialite());
    }

    /**
     * @testdox La collection de disponibilités d'un Médecin est vide à la création
     */
    public function testMedecinDisponibilitesVideesInitialement(): void
    {
        $medecin = new Medecin();

        $this->assertCount(0, $medecin->getDisponibilites());
    }

    /**
     * @testdox getNomComplet() retourne "prénom nom" pour un Médecin
     */
    public function testMedecinGetNomCompletRetournePrenomNom(): void
    {
        $medecin = new Medecin();
        $medecin->setNom('Martin');
        $medecin->setPrenom('Pierre');

        $this->assertSame('Pierre Martin', $medecin->getNomComplet());
    }

    /**
     * @testdox Un Médecin a createdAt défini automatiquement à la création
     */
    public function testMedecinCreatedAtEstDefiniALaCreation(): void
    {
        $medecin = new Medecin();

        $this->assertNotNull($medecin->getCreatedAt());
    }

    // =========================================================================
    // Tests de getRoles() — unicité
    // =========================================================================

    /**
     * @testdox getRoles() ne retourne pas de doublons même si ROLE_USER est ajouté en double
     */
    public function testGetRolesRetourneSansDoublons(): void
    {
        $patient = new Patient();
        // Forcer ROLE_USER en double
        $patient->setRoles(['ROLE_PATIENT', 'ROLE_USER']);

        $roles = $patient->getRoles();

        $this->assertSame(count($roles), count(array_unique($roles)));
    }
}
