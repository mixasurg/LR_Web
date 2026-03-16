<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /**
     * @return list<Page>
     */
    public function findVisibleForMenu(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->andWhere('p.showInMenu = :showInMenu')
            ->setParameter('published', true)
            ->setParameter('showInMenu', true)
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPublishedBySlug(string $slug): ?Page
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.slug = :slug')
            ->andWhere('p.isPublished = :published')
            ->setParameter('slug', mb_strtolower(trim($slug)))
            ->setParameter('published', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPublishedSystemPage(string $systemKey): ?Page
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.systemKey = :systemKey')
            ->andWhere('p.isPublished = :published')
            ->setParameter('systemKey', mb_strtolower(trim($systemKey)))
            ->setParameter('published', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
