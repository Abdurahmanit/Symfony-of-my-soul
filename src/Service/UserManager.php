<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class UserManager
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    public function getAllUsersQuery(): QueryBuilder
    {
        return $this->userRepository->createAllUsersQueryBuilder();
    }

    public function blockUser(User $user): void
    {
        if ($user->isBlocked()) {
            return;
        }
        $user->setIsBlocked(true);
        $this->entityManager->flush();
    }

    public function unblockUser(User $user): void
    {
        if (!$user->isBlocked()) {
            return;
        }
        $user->setIsBlocked(false);
        $this->entityManager->flush();
    }

    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function setAdmin(User $user): void
    {
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            $this->entityManager->flush();
        }
    }

    public function removeAdmin(User $user): void
    {
        $roles = $user->getRoles();
        $key = array_search('ROLE_ADMIN', $roles);
        if ($key !== false) {
            unset($roles[$key]);
            $user->setRoles(array_values($roles));
            $this->entityManager->flush();
        }
    }
}