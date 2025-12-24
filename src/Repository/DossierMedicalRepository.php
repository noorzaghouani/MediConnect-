<?php

namespace App\Repository;

use App\Entity\DossierMedical;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierMedical>
 *
 * @method DossierMedical|null find($id, $lockMode = null, $lockVersion = null)
 * @method DossierMedical|null findOneBy(array $criteria, array $orderBy = null)
 * @method DossierMedical[]    findAll()
 * @method DossierMedical[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierMedicalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierMedical::class);
    }
}
