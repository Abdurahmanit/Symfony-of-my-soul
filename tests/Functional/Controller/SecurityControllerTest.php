<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?UserPasswordHasherInterface $passwordHasher;

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
    }

    public function testRegisterNewUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Register');

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[plainPassword][first]' => 'password',
            'registration_form[plainPassword][second]' => 'password',
            'registration_form[agreeTerms]' => true,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/');
        $client->followRedirect();

        $this->assertSelectorTextContains('.navbar-nav', 'newuser@example.com');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'newuser@example.com']);
        $this->assertNotNull($user);
        $this->assertEquals('newuser@example.com', $user->getEmail());
    }

    public function testLoginUser(): void
    {
        $client = static::createClient();

        $user = new User();
        $user->setEmail('existing@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $crawler = $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'existing@example.com',
            'password' => 'password',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/');
        $client->followRedirect();

        $this->assertSelectorTextContains('.navbar-nav', 'existing@example.com');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert.alert-danger', 'Invalid credentials.');
    }

    public function testUserCannotLoginIfBlocked(): void
    {
        $client = static::createClient();

        $user = new User();
        $user->setEmail('blocked@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $user->setIsBlocked(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'blocked@example.com',
            'password' => 'password',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/login');
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert.alert-danger', 'Your account has been blocked. Please contact support.');
    }

    public function testLogout(): void
    {
        $client = static::createClient();

        $user = new User();
        $user->setEmail('logout@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $client->loginUser($user);

        $client->request('GET', '/logout');
        $this->assertResponseRedirects('/');
        $client->followRedirect();

        $this->assertSelectorTextNotContains('.navbar-nav', 'logout@example.com');
        $this->assertSelectorTextContains('.navbar-nav', 'Login');
    }
}