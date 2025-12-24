<?php

namespace App\Repository;

use App\Entity\RendezVous;
use App\Entity\Patient;
use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RendezVous>
 */
class RendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezVous::class);
    }

    /**
     * Trouve les rendez-vous d'un patient
     */
    public function findByPatient($patient)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('r.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }



    /**
     * Trouve les rendez-vous d'un médecin
     */
    public function findByMedecin($medecin)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('r.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rendez-vous à venir d'un patient
     */
    public function findProchainsRendezVous($patient)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.patient = :patient')
            ->andWhere('r.dateHeure >= :now')
            ->andWhere('r.statut != :annule')
            ->setParameter('patient', $patient)
            ->setParameter('now', new \DateTime())
            ->setParameter('annule', RendezVous::STATUT_ANNULE)
            ->orderBy('r.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rendez-vous à venir d'un médecin
     */
    public function findProchainsRendezVousMedecin($medecin)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.medecin = :medecin')
            ->andWhere('r.dateHeure >= :now')
            ->andWhere('r.statut != :annule')
            ->setParameter('medecin', $medecin)
            ->setParameter('now', new \DateTime())
            ->setParameter('annule', RendezVous::STATUT_ANNULE)
            ->orderBy('r.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rendez-vous par statut
     */
    public function findByStatut(string $statut)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('r.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rendez-vous entre deux dates
     */
    public function findBetweenDates(\DateTimeInterface $startDate, \DateTimeInterface $endDate)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.dateHeure BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rendez-vous du jour pour un médecin
     */
    public function findTodayRendezVous(Medecin $medecin)
    {
        $startOfDay = new \DateTime('today');
        $endOfDay = new \DateTime('tomorrow');

        return $this->createQueryBuilder('r')
            ->andWhere('r.medecin = :medecin')
            ->andWhere('r.dateHeure BETWEEN :startOfDay AND :endOfDay')
            ->andWhere('r.statut != :annule')
            ->setParameter('medecin', $medecin)
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->setParameter('annule', RendezVous::STATUT_ANNULE)
            ->orderBy('r.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un créneau est disponible pour un médecin
     */
    public function isSlotAvailable(Medecin $medecin, \DateTimeInterface $dateTime, int $durationMinutes = 30): bool
    {
        $endDateTime = \DateTimeImmutable::createFromInterface($dateTime)->modify("+{$durationMinutes} minutes");

        $conflictingRendezVous = $this->createQueryBuilder('r')
            ->andWhere('r.medecin = :medecin')
            ->andWhere('r.statut != :annule')
            ->andWhere('(r.dateHeure < :endDateTime AND DATE_ADD(r.dateHeure, 30, \'MINUTE\') > :dateTime)')
            ->setParameter('medecin', $medecin)
            ->setParameter('dateTime', $dateTime)
            ->setParameter('endDateTime', $endDateTime)
            ->setParameter('annule', RendezVous::STATUT_ANNULE)
            ->getQuery()
            ->getResult();

        return count($conflictingRendezVous) === 0;
    }

    /**
     * Compte les rendez-vous par statut pour un médecin
     */
    public function countByStatutForMedecin(Medecin $medecin, string $statut): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.medecin = :medecin')
            ->andWhere('r.statut = :statut')
            ->setParameter('medecin', $medecin)
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les rendez-vous expirés (date passée mais toujours en attente)
     */
    public function findExpiredRendezVous(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.dateHeure < :now')
            ->andWhere('r.statut = :attente')
            ->setParameter('now', new \DateTime())
            ->setParameter('attente', RendezVous::STATUT_ATTENTE)
            ->getQuery()
            ->getResult();
    }

    // Ajoutez cette méthode pour sauvegarder un rendez-vous
    public function save(RendezVous $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Ajoutez cette méthode pour supprimer un rendez-vous
    public function remove(RendezVous $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}