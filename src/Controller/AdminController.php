<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/users', name: 'app_admin_users')]
    public function userManagement(Request $request, UserManager $userManager, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $paginator->paginate(
            $userManager->getAllUsersQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/{id}/block', name: 'app_admin_user_block', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function blockUser(User $user, UserManager $userManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $userManager->blockUser($user);
            $this->addFlash('success', 'User blocked successfully.');
        } catch (OptimisticLockException $e) {
            $this->addFlash('error', 'User record was modified by another admin. Please reload and try again.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error blocking user: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/unblock', name: 'app_admin_user_unblock', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unblockUser(User $user, UserManager $userManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $userManager->unblockUser($user);
            $this->addFlash('success', 'User unblocked successfully.');
        } catch (OptimisticLockException $e) {
            $this->addFlash('error', 'User record was modified by another admin. Please reload and try again.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error unblocking user: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteUser(User $user, UserManager $userManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Prevent admin from deleting themselves
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'You cannot delete your own admin account.');
            return $this->redirectToRoute('app_admin_users');
        }

        try {
            $userManager->deleteUser($user);
            $this->addFlash('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting user: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/set-admin', name: 'app_admin_user_set_admin', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function setAdmin(User $user, UserManager $userManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $userManager->setAdmin($user);
            $this->addFlash('success', 'User granted admin access.');
        } catch (OptimisticLockException $e) {
            $this->addFlash('error', 'User record was modified by another admin. Please reload and try again.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error setting user as admin: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/remove-admin', name: 'app_admin_user_remove_admin', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeAdmin(User $user, UserManager $userManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'You cannot remove admin access from yourself.');
        } else {
            try {
                $userManager->removeAdmin($user);
                $this->addFlash('success', 'Admin access removed from user.');
            } catch (OptimisticLockException $e) {
                $this->addFlash('error', 'User record was modified by another admin. Please reload and try again.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error removing admin access: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_admin_users');
    }
}