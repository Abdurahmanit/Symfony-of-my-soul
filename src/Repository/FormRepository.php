<?php

namespace App\Repository;

use App\Entity\Form;
use App\Entity\Template;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class FormRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Form::class);
    }

    public function findUserFormsQueryBuilder(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.submittedAt', 'DESC');
    }

    public function findTemplateFormsQueryBuilder(Template $template): QueryBuilder
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.template = :template')
            ->setParameter('template', $template)
            ->orderBy('f.submittedAt', 'ASC');
    }
}