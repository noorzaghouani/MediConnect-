<?php

namespace App\Repository;

use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Medecin>
 *
 * @method Medecin|null find($id, $lockMode = null, $lockVersion = null)
 * @method Medecin|null findOneBy(array $criteria, array $orderBy = null)
 * @method Medecin[]    findAll()
 * @method Medecin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MedecinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medecin::class);
    }

    /**
     * Recherche les médecins vérifiés selon plusieurs critères
     * 
     * Effectue une recherche case-insensitive sur le nom, prénom et/ou spécialité.
     * Seuls les médecins vérifiés sont retournés.
     * Les résultats sont limités à 20 pour optimiser les performances.
     * 
     * @param string|null $nom Nom du médecin (recherche partielle)
     * @param string|null $prenom Prénom du médecin (recherche partielle)
     * @param string|null $specialite Nom de la spécialité médicale
     * @return Medecin[] Liste des médecins correspondants, triés par nom
     */
    public function searchMedecins(?string $nom = null, ?string $prenom = null, ?string $specialite = null): array
    {
        $qb = $this->createQueryBuilder('m');

        if ($nom && !empty(trim($nom))) {
            $qb->andWhere('LOWER(m.nom) LIKE LOWER(:nom)')
                ->setParameter('nom', '%' . trim($nom) . '%');
        }

        if ($prenom && !empty(trim($prenom))) {
            $qb->andWhere('LOWER(m.prenom) LIKE LOWER(:prenom)')
                ->setParameter('prenom', '%' . trim($prenom) . '%');
        }

        if ($specialite && !empty(trim($specialite))) {
            $qb->leftJoin('m.specialite', 's')
                ->andWhere('LOWER(s.nom) LIKE LOWER(:specialite)')
                ->setParameter('specialite', '%' . trim($specialite) . '%');
        }

        return $qb->andWhere('m.estVerifie = :verifie')
            ->setParameter('verifie', true)
            ->orderBy('m.nom', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }
    /**
     * Recherche des médecins par un terme unique
     * 
     * Effectue une recherche globale case-insensitive sur :
     * - Nom
     * - Prénom
     * - Email
     * - Nom de la spécialité
     * 
     * Utilise un LEFT JOIN avec addSelect pour éviter les requêtes N+1.
     * 
     * @param string $term Terme de recherche (recherche partielle)
     * @return Medecin[] Liste des médecins correspondants, triés par nom
     */
    public function searchByTerm(string $term): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.specialite', 's')
            ->addSelect('s')
            ->where('LOWER(m.nom) LIKE LOWER(:term)')
            ->orWhere('LOWER(m.prenom) LIKE LOWER(:term)')
            ->orWhere('LOWER(m.email) LIKE LOWER(:term)')
            ->orWhere('LOWER(COALESCE(s.nom, \'\')) LIKE LOWER(:term)')
            ->orderBy('m.nom', 'ASC')
            ->setParameter('term', '%' . $term . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne une page de médecins triés par nom.
     *
     * @param int $page   Numéro de page (commence à 1)
     * @param int $limit  Nombre d'entrées par page
     * @return Medecin[]
     */
    public function findPaginated(int $page, int $limit = 15): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.specialite', 's')
            ->addSelect('s')
            ->orderBy('m.nom', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le nombre total de médecins (pour calculer le nombre de pages).
     */
    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
