<?php

namespace App\Repository;

use App\Entity\Disponibilite;
use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DisponibiliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Disponibilite::class);
    }

    /**
     * Trouve toutes les disponibilités d'un médecin, triées par date
     */
    public function findByMedecinSorted(Medecin $medecin): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('d.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Sauvegarde une disponibilité
     */
    public function save(Disponibilite $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime une disponibilité
     */
    public function remove(Disponibilite $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
