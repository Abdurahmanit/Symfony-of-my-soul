<?php

namespace App\Service;

use App\Entity\Form; // Alias to avoid conflict with Symfony\Component\Form\Form
use App\Entity\Template;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class FormManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createForm(Form $formEntity, Template $template, User $user): void
    {
        $formEntity->setTemplate($template);
        $formEntity->setUser($user);
        $formEntity->setFixedUserEmail($user->getEmail()); // Capture user email at submission
        $formEntity->setFixedDate(new \DateTime()); // Capture submission date

        $this->entityManager->persist($formEntity);
        $this->entityManager->flush();
    }

    public function updateForm(Form $formEntity): void
    {
        // Optimistic locking will apply here automatically via Doctrine's flush
        $this->entityManager->flush();
    }

    public function deleteForm(Form $formEntity): void
    {
        $this->entityManager->remove($formEntity);
        $this->entityManager->flush();
    }
}