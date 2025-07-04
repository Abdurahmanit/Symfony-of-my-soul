<?php

namespace App\Repository;

use App\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class TemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    public function findLatestPublicTemplatesQuery(): Query
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.accessType = :accessType')
            ->setParameter('accessType', 'public')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery();
    }

    public function findMostPopularTemplates(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.accessType = :accessType')
            ->setParameter('accessType', 'public')
            ->orderBy('t.likes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function createSearchQueryBuilder(string $searchTerm): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.topic', 'topic')
            ->andWhere('t.accessType = :accessType')
            ->setParameter('accessType', 'public')
            ->andWhere('t.title ILIKE :searchTerm OR t.description ILIKE :searchTerm OR tag.name ILIKE :searchTerm OR topic.name ILIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('t.createdAt', 'DESC');
    }
}