<?php
// src/Repository/DossierMedicalRepository.php

namespace App\Repository;

use App\Entity\DossierMedical;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierMedical>
 */
class DossierMedicalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierMedical::class);
    }

    /**
     * Trouve un dossier médical par l'ID du patient
     */
    public function findByPatientId(int $patientId): ?DossierMedical
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.patient', 'p')
            ->andWhere('p.id = :patientId')
            ->setParameter('patientId', $patientId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les dossiers médicaux avec des allergies spécifiques
     */
    public function findByAllergie(string $allergie): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.allergies LIKE :allergie')
            ->setParameter('allergie', '%'.$allergie.'%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les dossiers médicaux modifiés après une certaine date
     */
    public function findModifiedAfter(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.dateModification > :date')
            ->setParameter('date', $date)
            ->orderBy('d.dateModification', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de dossiers médicaux
     */
    public function countDossiers(): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Sauvegarde un dossier médical avec timestamp automatique
     */
    public function save(DossierMedical $entity, bool $flush = false): void
    {
        $entity->setDateModification(new \DateTime());
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un dossier médical
     */
    public function remove(DossierMedical $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return DossierMedical[] Returns an array of DossierMedical objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DossierMedical
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}