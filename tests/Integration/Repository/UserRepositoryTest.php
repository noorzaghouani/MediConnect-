<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\Speciality;
use App\Repository\UserRepository;
use App\Tests\Integration\DatabaseTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class UserRepositoryTest extends DatabaseTestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var UserRepository $repo */
        $repo = $this->entityManager->getRepository(\App\Entity\User::class);
        $this->userRepository = $repo;
    }

    /**
     * @testdox Persistance et récupération d'un Patient avec héritage JOINED
     */
    public function testPersistAndFindPatient(): void
    {
        $patient = new Patient();
        $patient->setEmail('patient.test@mediconnect.com');
        $patient->setPassword('hashpassword123');
        $patient->setNom('Dupont');
        $patient->setPrenom('Jean');
        $patient->setTelephone('0612345678');
        $patient->setGenre('M');
        $patient->setDateNaissance(new \DateTime('1990-05-15'));

        $this->entityManager->persist($patient);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $trouve = $this->userRepository->findOneBy(['email' => 'patient.test@mediconnect.com']);

        $this->assertInstanceOf(Patient::class, $trouve);
        $this->assertSame('Jean Dupont', $trouve->getNomComplet());
        $this->assertContains('ROLE_PATIENT', $trouve->getRoles());
    }

    /**
     * @testdox Persistance d'un Médecin avec statut estVerifie et Spécialité associée
     */
    public function testPersistMedecinWithSpecialiteAndVerification(): void
    {
        $specialite = new Speciality();
        $specialite->setNom('Neurologie');
        $this->entityManager->persist($specialite);

        $medecin = new Medecin();
        $medecin->setEmail('dr.curie@mediconnect.com');
        $medecin->setPassword('hashpassword456');
        $medecin->setNom('Curie');
        $medecin->setPrenom('Marie');
        $medecin->setTelephone('0698765432');
        $medecin->setGenre('F');
        $medecin->setDateNaissance(new \DateTime('1985-11-07'));
        $medecin->setDiplome('diplome_curie.pdf');
        $medecin->setSpecialite($specialite);
        $medecin->setEstVerifie(true);

        $this->entityManager->persist($medecin);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $medecinRepo = $this->entityManager->getRepository(Medecin::class);
        /** @var Medecin|null $trouve */
        $trouve = $medecinRepo->findOneBy(['email' => 'dr.curie@mediconnect.com']);

        $this->assertNotNull($trouve);
        $this->assertTrue($trouve->isEstVerifie());
        $this->assertNotNull($trouve->getSpecialite());
        $this->assertSame('Neurologie', $trouve->getSpecialite()->getNom());
    }

    /**
     * @testdox Filtrage des médecins non vérifiés dans la base de données
     */
    public function testFilterMedecinsNonVerifies(): void
    {
        $med1 = new Medecin();
        $med1->setEmail('med1@test.com');
        $med1->setPassword('pass123456');
        $med1->setNom('Martin');
        $med1->setPrenom('Paul');
        $med1->setTelephone('0611111111');
        $med1->setGenre('M');
        $med1->setDateNaissance(new \DateTime('1980-01-01'));
        $med1->setEstVerifie(false);

        $med2 = new Medecin();
        $med2->setEmail('med2@test.com');
        $med2->setPassword('pass123456');
        $med2->setNom('Bernard');
        $med2->setPrenom('Sophie');
        $med2->setTelephone('0622222222');
        $med2->setGenre('F');
        $med2->setDateNaissance(new \DateTime('1982-02-02'));
        $med2->setEstVerifie(true);

        $this->entityManager->persist($med1);
        $this->entityManager->persist($med2);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $medecinRepo = $this->entityManager->getRepository(Medecin::class);
        $nonVerifies = $medecinRepo->findBy(['estVerifie' => false]);

        $this->assertCount(1, $nonVerifies);
        $this->assertSame('med1@test.com', $nonVerifies[0]->getEmail());
    }

    /**
     * @testdox Contrainte d'unicité sur l'adresse email des utilisateurs
     */
    public function testUniqueEmailConstraint(): void
    {
        $user1 = new Patient();
        $user1->setEmail('unique@mediconnect.com');
        $user1->setPassword('pass1');
        $user1->setNom('Test1');
        $user1->setPrenom('User1');
        $user1->setTelephone('0600000001');
        $user1->setGenre('M');
        $user1->setDateNaissance(new \DateTime('1995-01-01'));
        $this->entityManager->persist($user1);
        $this->entityManager->flush();

        $user2 = new Patient();
        $user2->setEmail('unique@mediconnect.com');
        $user2->setPassword('pass2');
        $user2->setNom('Test2');
        $user2->setPrenom('User2');
        $user2->setTelephone('0600000002');
        $user2->setGenre('F');
        $user2->setDateNaissance(new \DateTime('1996-02-02'));
        $this->entityManager->persist($user2);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->entityManager->flush();
    }
}
