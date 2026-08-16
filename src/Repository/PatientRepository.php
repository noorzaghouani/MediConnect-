<?php

namespace App\Repository;

use App\Entity\Patient;
use App\Entity\RendezVous;
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

    /**
     * Trouve tous les patients ayant au moins un rendez-vous confirmé avec le médecin.
     * Utilise JOIN avec addSelect pour éviter les requêtes N+1.
     *
     * @param \App\Entity\Medecin $medecin
     * @return Patient[]
     */
    public function findPatientsByMedecin($medecin)
    {
        return $this->createQueryBuilder('p')
            ->join('p.rendezVous', 'r')
            ->addSelect('r')
            ->where('r.medecin = :medecin')
            ->andWhere('r.statut = :statut')
            ->setParameter('medecin', $medecin)
            ->setParameter('statut', RendezVous::STATUT_CONFIRME)
            ->distinct()
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les patients qui ont au moins une consultation avec le médecin
     * (utilisé pour restreindre la création d'ordonnances).
     *
     * @param \App\Entity\Medecin $medecin
     * @return Patient[]
     */
    public function findPatientsWithConsultationsByMedecin($medecin)
    {
        return $this->createQueryBuilder('p')
            ->join('p.dossierMedical', 'dm')
            ->addSelect('dm')
            ->join('dm.consultations', 'c')
            ->addSelect('c')
            ->where('c.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->distinct()
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des patients par un terme unique (nom, prénom, email, téléphone).
     *
     * @param string $term
     * @return Patient[]
     */
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

    /**
     * Retourne une page de patients triés par nom.
     *
     * @param int $page   Numéro de page (commence à 1)
     * @param int $limit  Nombre d'entrées par page
     * @return Patient[]
     */
    public function findPaginated(int $page, int $limit = 15): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.nom', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le nombre total de patients (pour calculer le nombre de pages).
     */
    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les patients d'un médecin avec leur prochain rendez-vous.
     * Charge tous les RDV en une seule requête (addSelect) pour éviter les N+1.
     *
     * @param \App\Entity\Medecin $medecin
     * @return array[] [['patient' => Patient, 'nextRdv' => RendezVous|null]]
     */
    public function findPatientsWithNextRdvByMedecin($medecin): array
    {
        $patients = $this->createQueryBuilder('p')
            ->join('p.rendezVous', 'r')
            ->addSelect('r')
            ->where('r.medecin = :medecin')
            ->andWhere('r.statut = :statut')
            ->setParameter('medecin', $medecin)
            ->setParameter('statut', 'confirme')
            ->distinct()
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();

        $now = new \DateTime();
        $result = [];

        foreach ($patients as $patient) {
            $nextRdv = null;
            $upcomingRdvs = [];

            foreach ($patient->getRendezVous() as $rdv) {
                if (
                    $rdv->getMedecin() === $medecin
                    && $rdv->getDateHeure() > $now
                    && $rdv->getStatut() === 'confirme'
                ) {
                    $upcomingRdvs[] = $rdv;
                }
            }

            if (!empty($upcomingRdvs)) {
                usort($upcomingRdvs, function ($a, $b) {
                    return $a->getDateHeure() <=> $b->getDateHeure();
                });
                $nextRdv = $upcomingRdvs[0];
            }

            $result[] = [
                'patient' => $patient,
                'nextRdv' => $nextRdv
            ];
        }

        return $result;
    }
}
