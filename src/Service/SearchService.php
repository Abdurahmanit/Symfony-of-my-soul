<?php

namespace App\Service;

use App\Entity\Template;
use App\Entity\Tag;
use App\Repository\TemplateRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Knp\Component\Pager\PaginatorInterface;

class SearchService
{
    private EntityManagerInterface $entityManager;
    private PaginatorInterface $paginator;
    private TemplateRepository $templateRepository;
    private TagRepository $tagRepository;
    private int $itemsPerPage;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        TemplateRepository $templateRepository,
        TagRepository $tagRepository,
        int $itemsPerPage
    ) {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
        $this->templateRepository = $templateRepository;
        $this->tagRepository = $tagRepository;
        $this->itemsPerPage = $itemsPerPage;
    }

    public function searchTemplatesQuery(string $searchTerm): Query
    {
        return $this->templateRepository->createSearchQueryBuilder($searchTerm)->getQuery();
    }

    public function getLatestTemplates(): Query
    {
        return $this->templateRepository->findLatestPublicTemplatesQuery();
    }

    public function getPopularTemplates(int $limit = 5): array
    {
        return $this->templateRepository->findMostPopularTemplates($limit);
    }

    public function getTagCloud(): array
    {
        // Implement logic to get most used tags for tag cloud
        // This is a placeholder; you might need a custom repository method for this.
        return $this->tagRepository->createQueryBuilder('t')
            ->select('t.name as tag_name, COUNT(t.id) as tag_count')
            ->leftJoin('t.templates', 'temp')
            ->groupBy('t.name')
            ->orderBy('tag_count', 'DESC')
            ->setMaxResults(30) // Limit tags in the cloud
            ->getQuery()
            ->getArrayResult();
    }
}