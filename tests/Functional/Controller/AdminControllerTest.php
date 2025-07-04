<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;

class AdminControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?UserPasswordHasherInterface $passwordHasher;
    private ?User $adminUser;
    private ?User $regularUser;

    protected function setUp(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE comment RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE form RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE question RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE template_liked_by RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE template_restricted_access RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE template_tags RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE template RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE tag RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE topic RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE "user" RESTART IDENTITY CASCADE');

        $this->regularUser = new User();
        $this->regularUser->setEmail('regular@example.com');
        $this->regularUser->setPassword($this->passwordHasher->hashPassword($this->regularUser, 'password'));
        $this->regularUser->setRoles(['ROLE_USER']);
        $this->entityManager->persist($this->regularUser);

        $this->adminUser = new User();
        $this->adminUser->setEmail('admin@example.com');
        $this->adminUser->setPassword($this->passwordHasher->hashPassword($this->adminUser, 'admin_password'));
        $this->adminUser->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($this->adminUser);

        $this->entityManager->flush();
    }

    private function loginAsAdmin($client): void
    {
        $client->loginUser($this->adminUser);
    }

    public function testAdminUsersPageRequiresAdminRole(): void
    {
        $client = static::createClient();
        $client->loginUser($this->regularUser);

        $client->request('GET', '/admin/users');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminUsersPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/users');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Admin - User Management');
        $this->assertSelectorTextContains('td', 'regular@example.com');
        $this->assertSelectorTextContains('td', 'admin@example.com');
    }

    public function testBlockUser(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('POST', '/admin/user/' . $this->regularUser->getId() . '/block', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('user_block_unblock' . $this->regularUser->getId())->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/users');
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'User blocked successfully.');
        $blockedUser = $this->entityManager->getRepository(User::class)->find($this->regularUser->getId());
        $this->assertTrue($blockedUser->isBlocked());
    }

    public function testUnblockUser(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        // First block the user
        $this->regularUser->setIsBlocked(true);
        $this->entityManager->flush();

        $crawler = $client->request('POST', '/admin/user/' . $this->regularUser->getId() . '/unblock', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('user_block_unblock' . $this->regularUser->getId())->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/users');
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'User unblocked successfully.');
        $unblockedUser = $this->entityManager->getRepository(User::class)->find($this->regularUser->getId());
        $this->assertFalse($unblockedUser->isBlocked());
    }

    public function testDeleteUser(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $userToDeleteId = $this->regularUser->getId();
        $crawler = $client->request('POST', '/admin/user/' . $userToDeleteId . '/delete', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('user_delete' . $userToDeleteId)->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/users');
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'User deleted successfully.');
        $deletedUser = $this->entityManager->getRepository(User::class)->find($userToDeleteId);
        $this->assertNull($deletedUser);
    }

    public function testDeleteSelfAsAdminFails(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $adminUserId = $this->adminUser->getId();
        $crawler = $client->request('POST', '/admin/user/' . $adminUserId . '/delete', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('user_delete' . $adminUserId)->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/users');
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert-error', 'You cannot delete your own admin account.');
        $adminStillExists = $this->entityManager->getRepository(User::class)->find($adminUserId);
        $this->assertNotNull($adminStillExists);
    }

    public function testSetAdminRole(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $userToMakeAdmin = $this->regularUser;
        $crawler = $client->request('POST', '/admin/user/' . $userToMakeAdmin->getId() . '/set-admin', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('user_admin_role' . $userToMakeAdmin->getId())->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/users');
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'User granted admin access.');
        $updatedUser = $this->entityManager->getRepository(User::class)->find($userToMakeAdmin->getId());
        $this->assertContains('ROLE_ADMIN', $updatedUser->getRoles());
    }

    public function testRemoveAdminFromSelf(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        // This test case is specifically for removing self. The requirement states:
        // "ADMIN IS ABLE TO REMOVE ADMIN ACCESS FROM ITSELF; it’s important."
        // My previous implementation had a check to prevent this. I will adjust the test.
        // It should *succeed* if there is at least one other admin.
        // If it's the *only* admin, it should prevent removal to avoid locking out.

        // First, create a second admin to ensure we don't lock ourselves out
        $secondAdmin = new User();
        $secondAdmin->setEmail('second_admin@example.com');
        $secondAdmin->setPassword($this->passwordHasher->hashPassword($secondAdmin, 'password'));
        $secondAdmin->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($secondAdmin);
        $this->entityManager->flush();

        // Reload current admin user from DB for fresh state after flush
        $this->adminUser = $this->entityManager->getRepository(User::class)->find($this->adminUser->getId());

        $client->request('POST', '/admin/user/' . $this->adminUser->getId() . '/remove-admin', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('user_admin_role' . $this->adminUser->getId())->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/users');
        $client->followRedirect();

        // This message depends on the business logic. If it allows removing self
        // when there's another admin, the message should be success.
        $this->assertSelectorTextContains('.alert-success', 'Admin access removed from user.');
        $updatedUser = $this->entityManager->getRepository(User::class)->find($this->adminUser->getId());
        $this->assertNotContains('ROLE_ADMIN', $updatedUser->getRoles());
        $this->assertContains('ROLE_USER', $updatedUser->getRoles());
    }
}