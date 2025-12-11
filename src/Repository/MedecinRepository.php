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
     * Search doctors by name, firstname, or specialty
     * @return Medecin[]
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

        return $qb->orderBy('m.nom', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }
}

