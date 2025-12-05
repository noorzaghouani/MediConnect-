<?php

namespace App\Repository;

use App\Entity\Administrateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Administrateur>
 *
 * @method Administrateur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Administrateur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Administrateur[]    findAll()
 * @method Administrateur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdministrateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Administrateur::class);
    }

//    /**
//     * @return Administrateur[] Returns an array of Administrateur objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Administrateur
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
