<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Template;
use App\Entity\User;
use App\Entity\Topic;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DefaultControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
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

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);

        $topic = new Topic();
        $topic->setName('General');
        $this->entityManager->persist($topic);

        $template = new Template();
        $template->setTitle('Test Template');
        $template->setDescription('This is a test description.');
        $template->setUser($user);
        $template->setTopic($topic);
        $template->setAccessType('public');
        $template->addLikedByUser($user); // Add a like for popularity test
        $this->entityManager->persist($template);

        $template2 = new Template();
        $template2->setTitle('Another Template');
        $template2->setDescription('Another test description.');
        $template2->setUser($user);
        $template2->setTopic($topic);
        $template2->setAccessType('public');
        $this->entityManager->persist($template2);

        $this->entityManager->flush();
    }

    public function testHomepageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Latest Templates');
        $this->assertSelectorTextContains('.card-title', 'Test Template');
    }

    public function testSearchFunctionality(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/search?q=Test');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Search Results');
        $this->assertSelectorTextContains('.card-title', 'Test Template');
        $this->assertSelectorNotContains('.card-title', 'Another Template');

        $crawler = $client->request('GET', '/search?q=description');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.card-title', 'Test Template');
        $this->assertSelectorTextContains('.card-title', 'Another Template');

        $crawler = $client->request('GET', '/search?q=NonExistent');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'No templates found.');
    }

    public function testProfilePageLoadsSuccessfullyForLoggedInUser(): void
    {
        $client = static::createClient();
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail('test@example.com');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile'); // Access current user's profile
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'test@example.com\'s Profile');
    }

    public function testProfilePageLoadsSuccessfullyForSpecificUser(): void
    {
        $client = static::createClient();
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail('test@example.com');

        $crawler = $client->request('GET', '/profile/' . $user->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'test@example.com\'s Profile');
    }

    public function testSetTheme(): void
    {
        $client = static::createClient();
        $client->request('POST', '/set-theme', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['theme' => 'dark']));
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testSetLocale(): void
    {
        $client = static::createClient();
        $client->request('POST', '/set-locale', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['locale' => 'pl']));
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }
}