<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Template;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CommentService
{
    private EntityManagerInterface $entityManager;
    private PublisherInterface $publisher;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, PublisherInterface $publisher, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->publisher = $publisher;
        $this->urlGenerator = $urlGenerator;
    }

    public function addCommentToTemplate(Template $template, User $user, string $content): Comment
    {
        $comment = new Comment();
        $comment->setTemplate($template);
        $comment->setUser($user);
        $comment->setContent($content);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        // Publish to Mercure for real-time updates
        $topic = $this->urlGenerator->generate('app_template_show', ['id' => $template->getId()], UrlGeneratorInterface::ABS_URL);
        $update = new Update(
            $topic,
            json_encode([
                'id' => $comment->getId(),
                'userEmail' => $user->getEmail(),
                'content' => $content,
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                'templateId' => $template->getId(),
            ]),
            false, // Private (only authenticated user of this app can receive)
            null, // No specific ID
            null, // No type
            null // No retry
        );

        $this->publisher->__invoke($update);

        return $comment;
    }
}