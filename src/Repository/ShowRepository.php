<?php

namespace App\Repository;

use App\Entity\Show;
use App\Service\SafeEntityManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Show>
 */
class ShowRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry, private SafeEntityManager $safe)
  {
      parent::__construct($registry, Show::class);
  }

  /**
   * @return Show[] Returns an array of Show objects
   */
  public function findUpcoming(): array
  {
     return $this->safe->safe(function($em) {
            return $em->createQueryBuilder('s')
            ->from(Show::class, 's')
            ->andWhere('s.date > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.date', 'ASC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult()
              ;
            }
     )
       ;
  }
}
