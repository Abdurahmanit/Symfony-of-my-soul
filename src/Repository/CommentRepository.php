<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findCommentsForTemplate(Template $template, int $limit = 50): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.template = :template')
            ->setParameter('template', $template)
            ->orderBy('c.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}