<?php

namespace App\Repository;

use App\Entity\Patient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Patient>
 *
 * @method Patient|null find($id, $lockMode = null, $lockVersion = null)
 * @method Patient|null findOneBy(array $criteria, array $orderBy = null)
 * @method Patient[]    findAll()
 * @method Patient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PatientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Patient::class);
    }

    public function findPatientsByMedecin($medecin)
    {
        return $this->createQueryBuilder('p')
            ->join('p.rendezVous', 'r')
            ->where('r.medecin = :medecin')
            ->andWhere('r.statut = :statut')
            ->setParameter('medecin', $medecin)
            ->setParameter('statut', 'confirme')
            ->distinct()
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les patients qui ont au moins une consultation avec le médecin
     * (pour créer une ordonnance)
     */
    public function findPatientsWithConsultationsByMedecin($medecin)
    {
        return $this->createQueryBuilder('p')
            ->join('p.dossierMedical', 'dm')
            ->join('dm.consultations', 'c')
            ->where('c.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->distinct()
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function searchByTerm(string $term): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.nom LIKE :term')
            ->orWhere('p.prenom LIKE :term')
            ->orWhere('p.email LIKE :term')
            ->orWhere('p.telephone LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
