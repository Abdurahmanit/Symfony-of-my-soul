<?php

namespace App\Service;

use App\Entity\Template;
use App\Entity\User;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class TemplateManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createTemplate(Template $template, User $user): void
    {
        $template->setUser($user);
        $this->entityManager->persist($template);
        $this->entityManager->flush();
    }

    public function updateTemplate(Template $template): void
    {
        // When updating, the template object already contains the version from the form
        // Doctrine's ORM will automatically handle the optimistic locking check upon flush.
        // If the version doesn't match the DB, an OptimisticLockException will be thrown.
        $this->entityManager->flush();
    }

    public function deleteTemplate(Template $template): void
    {
        $this->entityManager->remove($template);
        $this->entityManager->flush();
    }

    public function addQuestionToTemplate(Template $template, array $questionData): Question
    {
        $question = new Question();
        $question->setTitle($questionData['title'] ?? '');
        $question->setDescription($questionData['description'] ?? null);
        $question->setType($questionData['type'] ?? 'string');
        $question->setShowInTable($questionData['showInTable'] ?? false);
        // Position will be set by Template entity's addQuestion method

        $template->addQuestion($question);
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $question;
    }

    public function updateQuestionInTemplate(Question $question, array $questionData): void
    {
        $question->setTitle($questionData['title'] ?? $question->getTitle());
        $question->setDescription($questionData['description'] ?? $question->getDescription());
        $question->setType($questionData['type'] ?? $question->getType());
        $question->setShowInTable($questionData['showInTable'] ?? $question->isShowInTable());
        $question->setPosition($questionData['position'] ?? $question->getPosition());

        $this->entityManager->flush();
    }

    public function removeQuestionFromTemplate(Question $question): void
    {
        $template = $question->getTemplate();
        if ($template) {
            $template->removeQuestion($question);
            $this->entityManager->persist($template); // Re-persist template to trigger reordering
        }
        $this->entityManager->remove($question);
        $this->entityManager->flush();
    }

    public function reorderQuestions(Template $template, array $newOrderIds): void
    {
        $questions = $template->getQuestions()->toArray();
        $indexedQuestions = [];
        foreach ($questions as $q) {
            $indexedQuestions[$q->getId()] = $q;
        }

        $position = 0;
        foreach ($newOrderIds as $questionId) {
            if (isset($indexedQuestions[$questionId])) {
                $indexedQuestions[$questionId]->setPosition($position++);
            }
        }
        $this->entityManager->flush();
    }
}