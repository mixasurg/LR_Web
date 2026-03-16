<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Work;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Work>
 */
class WorkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Work::class);
    }

    /**
     * @return list<Work>
     */
    public function findPublishedOrdered(): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.photos', 'p', 'WITH', 'p.isPublished = :photoPublished')
            ->addSelect('p')
            ->andWhere('w.isPublished = :published')
            ->setParameter('published', true)
            ->setParameter('photoPublished', true)
            ->orderBy('w.sortOrder', 'ASC')
            ->addOrderBy('w.title', 'ASC')
            ->addOrderBy('p.sortOrder', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPublishedBySlug(string $slug): ?Work
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.photos', 'p', 'WITH', 'p.isPublished = :photoPublished')
            ->addSelect('p')
            ->andWhere('w.slug = :slug')
            ->andWhere('w.isPublished = :published')
            ->setParameter('slug', mb_strtolower(trim($slug)))
            ->setParameter('published', true)
            ->setParameter('photoPublished', true)
            ->orderBy('p.sortOrder', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
