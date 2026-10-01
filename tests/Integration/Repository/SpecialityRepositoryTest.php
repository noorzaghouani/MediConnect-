<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Speciality;
use App\Repository\SpecialityRepository;
use App\Tests\Integration\DatabaseTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class SpecialityRepositoryTest extends DatabaseTestCase
{
    private SpecialityRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var SpecialityRepository $repo */
        $repo = $this->entityManager->getRepository(Speciality::class);
        $this->repository = $repo;
    }

    /**
     * @testdox Persistance et récupération d'une spécialité par nom
     */
    public function testPersistAndFindSpeciality(): void
    {
        $specialite = new Speciality();
        $specialite->setNom('Cardiologie');
        $specialite->setDescription('Maladies cardio-vasculaires');

        $this->entityManager->persist($specialite);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $trouvee = $this->repository->findOneBy(['nom' => 'Cardiologie']);

        $this->assertNotNull($trouvee);
        $this->assertSame('Cardiologie', $trouvee->getNom());
        $this->assertSame('Maladies cardio-vasculaires', $trouvee->getDescription());
    }

    /**
     * @testdox Contrainte d'unicité sur le nom de la spécialité
     */
    public function testUniqueConstraintOnNom(): void
    {
        $spec1 = new Speciality();
        $spec1->setNom('Pédiatrie');
        $this->entityManager->persist($spec1);
        $this->entityManager->flush();

        $spec2 = new Speciality();
        $spec2->setNom('Pédiatrie');
        $this->entityManager->persist($spec2);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->entityManager->flush();
    }

    /**
     * @testdox Suppression d'une spécialité en base de données
     */
    public function testDeleteSpeciality(): void
    {
        $spec = new Speciality();
        $spec->setNom('Dermatologie');
        $this->entityManager->persist($spec);
        $this->entityManager->flush();

        $id = $spec->getId();
        $this->assertNotNull($id);

        $this->entityManager->remove($spec);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->assertNull($this->repository->find($id));
    }
}
