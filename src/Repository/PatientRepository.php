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
     * Trouve tous les patients ayant au moins un rendez-vous confirmé avec le médecin
     * 
     * Utilise JOIN avec addSelect pour éviter les requêtes N+1.
     * Retourne uniquement les patients avec statut de RDV "confirmé".
     * 
     * @param \App\Entity\Medecin $medecin Le médecin dont on recherche les patients
     * @return Patient[] Liste des patients distincts, triés par nom
     */
    public function findPatientsByMedecin($medecin)
    {
        return $this->createQueryBuilder('p')
            ->join('p.rendezVous', 'r')
            ->addSelect('r')  // Évite les requêtes N+1
            ->where('r.medecin = :medecin')
            ->andWhere('r.statut = :statut')
            ->setParameter('medecin', $medecin)
            ->setParameter('statut', RendezVous::STATUT_CONFIRME)  // Utilisation de la constante
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
            ->addSelect('dm')  // Évite les requêtes N+1
            ->join('dm.consultations', 'c')
            ->addSelect('c')  // Évite les requêtes N+1
            ->where('c.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->distinct()
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
    /**
     * Recherche des patients par un terme unique
     * 
     *Effectue une recherche case-sensitive partielle sur:
     * - Nom
     * - Prénom
     * - Email
     * - Numéro de téléphone
     * 
     * @param string $term Terme de recherche (recherche partielle)
     * @return Patient[] Liste des patients correspondants, triés par nom
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
     * Trouve les patients d'un médecin avec leur prochain rendez-vous
     * 
     * Méthode optimisée pour éviter les requêtes N+1:
     * - Charge tous les patients avec au moins un RDV confirmé
     * - Pour chaque patient, trouve le prochain RDV à venir
     * 
     * Retourne un tableau associatif contenant:
     * - 'patient': l'objet Patient
     * - 'nextRdv': le prochain RendezVous confirmé ou null
     * 
     * @param \App\Entity\Medecin $medecin Le médecin concerné
     * @return array[] Liste de tableaux ['patient' => Patient, 'nextRdv' => RendezVous|null]
     */
    public function findPatientsWithNextRdvByMedecin($medecin): array
    {
        // Récupérer tous les patients ayant eu un RDV CONFIRMÉ avec ce médecin
        $patients = $this->createQueryBuilder('p')
            ->join('p.rendezVous', 'r')
            ->where('r.medecin = :medecin')
            ->andWhere('r.statut = :statut')  // ✅ Filtrer seulement RDV confirmés
            ->setParameter('medecin', $medecin)
            ->setParameter('statut', 'confirme')
            ->distinct()
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();

        $now = new \DateTime();
        $result = [];

        foreach ($patients as $patient) {
            // Chercher le prochain RDV confirmé dans les RDV déjà chargés en mémoire
            $nextRdv = null;
            $upcomingRdvs = [];

            foreach ($patient->getRendezVous() as $rdv) {
                if (
                    $rdv->getMedecin() === $medecin
                    && $rdv->getDateHeure() > $now
                    && $rdv->getStatut() === 'confirme'  // ✅ Seulement RDV confirmés
                ) {
                    $upcomingRdvs[] = $rdv;
                }
            }

            // Trier et prendre le premier
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
