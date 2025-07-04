<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Template;
use App\Entity\User;
use App\Entity\Topic;
use App\Entity\Question;
use App\Entity\Form as FilledForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;

class TemplateControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?UserPasswordHasherInterface $passwordHasher;
    private ?User $user;
    private ?User $admin;
    private ?Topic $topic;

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

        $this->user = new User();
        $this->user->setEmail('user@example.com');
        $this->user->setPassword($this->passwordHasher->hashPassword($this->user, 'password'));
        $this->user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($this->user);

        $this->admin = new User();
        $this->admin->setEmail('admin@example.com');
        $this->admin->setPassword($this->passwordHasher->hashPassword($this->admin, 'admin_password'));
        $this->admin->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($this->admin);

        $this->topic = new Topic();
        $this->topic->setName('Test Topic');
        $this->entityManager->persist($this->topic);

        $this->entityManager->flush();
    }

    private function createTemplate(string $title, string $accessType = 'public', ?User $owner = null): Template
    {
        $template = new Template();
        $template->setTitle($title);
        $template->setDescription('Some description');
        $template->setUser($owner ?? $this->user);
        $template->setTopic($this->topic);
        $template->setAccessType($accessType);
        $this->entityManager->persist($template);
        $this->entityManager->flush();
        return $template;
    }

    private function createQuestion(Template $template, string $title, string $type, bool $showInTable = false): Question
    {
        $question = new Question();
        $question->setTemplate($template);
        $question->setTitle($title);
        $question->setType($type);
        $question->setShowInTable($showInTable);
        $this->entityManager->persist($question);
        $template->addQuestion($question); // Ensure template's collection is updated
        $this->entityManager->flush();
        return $question;
    }

    public function testNewTemplateRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/template/new');
        $this->assertResponseRedirects('/login');
    }

    public function testNewTemplateSubmission(): void
    {
        $client = static::createClient();
        $client->loginUser($this->user);

        $crawler = $client->request('GET', '/template/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save Template')->form([
            'template[title]' => 'My New Template',
            'template[description]' => 'A description for my new template.',
            'template[topic]' => $this->topic->getId(),
            'template[accessType]' => 'public',
            // frontend submits questions and tags via JS, typically as JSON
            // For functional tests simulating a form, you might need to adjust FormType or send raw JSON
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/template/' . $this->entityManager->getRepository(Template::class)->findOneBy(['title' => 'My New Template'])->getId());
        $client->followRedirect();

        $this->assertSelectorTextContains('h1', 'My New Template');
        $this->assertSelectorTextContains('.alert-success', 'Template created successfully!');
    }

    public function testShowPublicTemplate(): void
    {
        $template = $this->createTemplate('Public Template');
        $client = static::createClient();
        $client->request('GET', '/template/' . $template->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Public Template');
    }

    public function testShowRestrictedTemplateRequiresAccess(): void
    {
        $template = $this->createTemplate('Restricted Template', 'restricted');
        $client = static::createClient();

        // Non-logged in user
        $client->request('GET', '/template/' . $template->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Another logged-in user without access
        $otherUser = new User();
        $otherUser->setEmail('other@example.com');
        $otherUser->setPassword($this->passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_USER']);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();
        $client->loginUser($otherUser);

        $client->request('GET', '/template/' . $template->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testEditTemplateRequiresOwnershipOrAdmin(): void
    {
        $template = $this->createTemplate('My Template for Editing');
        $client = static::createClient();

        // Non-logged in user
        $client->request('GET', '/template/' . $template->getId() . '/edit');
        $this->assertResponseRedirects('/login');

        // Another logged-in user
        $otherUser = new User();
        $otherUser->setEmail('other@example.com');
        $otherUser->setPassword($this->passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_USER']);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();
        $client->loginUser($otherUser);

        $client->request('GET', '/template/' . $template->getId() . '/edit');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testEditTemplateAsOwner(): void
    {
        $template = $this->createTemplate('Template to Edit', 'public', $this->user);
        $client = static::createClient();
        $client->loginUser($this->user);

        $crawler = $client->request('GET', '/template/' . $template->getId() . '/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorFieldIsNotDisabled('template_title');

        $form = $crawler->selectButton('Save Template')->form([
            'template[title]' => 'Updated Template Title',
            'template[description]' => 'Updated description.',
            'template[topic]' => $this->topic->getId(),
            'template[accessType]' => 'public',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/template/' . $template->getId());
        $client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Updated Template Title');
        $this->assertSelectorTextContains('.alert-success', 'Template updated successfully!');

        $updatedTemplate = $this->entityManager->getRepository(Template::class)->find($template->getId());
        $this->assertEquals('Updated Template Title', $updatedTemplate->getTitle());
    }

    public function testEditTemplateAsAdmin(): void
    {
        $template = $this->createTemplate('Admin Editable Template', 'public', $this->user);
        $client = static::createClient();
        $client->loginUser($this->admin);

        $crawler = $client->request('GET', '/template/' . $template->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save Template')->form([
            'template[title]' => 'Admin Updated Template',
            'template[description]' => 'Admin updated description.',
            'template[topic]' => $this->topic->getId(),
            'template[accessType]' => 'public',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/template/' . $template->getId());
        $client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Admin Updated Template');
        $this->assertSelectorTextContains('.alert-success', 'Template updated successfully!');
    }

    public function testDeleteTemplateRequiresOwnershipOrAdmin(): void
    {
        $template = $this->createTemplate('Template to Delete');
        $templateId = $template->getId();
        $client = static::createClient();

        // Non-logged in user
        $client->request('POST', '/template/' . $templateId . '/delete');
        $this->assertResponseRedirects('/login');

        // Another logged-in user
        $otherUser = new User();
        $otherUser->setEmail('other_deleter@example.com');
        $otherUser->setPassword($this->passwordHasher->hashPassword($otherUser, 'password'));
        $otherUser->setRoles(['ROLE_USER']);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();
        $client->loginUser($otherUser);

        $client->request('POST', '/template/' . $templateId . '/delete');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteTemplateAsOwner(): void
    {
        $template = $this->createTemplate('Owner Delete Template', 'public', $this->user);
        $templateId = $template->getId();
        $client = static::createClient();
        $client->loginUser($this->user);

        $crawler = $client->request('POST', '/template/' . $templateId . '/delete', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete' . $templateId)->getValue(),
        ]);

        $this->assertResponseRedirects('/templates');
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'Template deleted successfully!');
        $this->assertNull($this->entityManager->getRepository(Template::class)->find($templateId));
    }

    public function testFillForm(): void
    {
        $template = $this->createTemplate('Form Fill Template', 'public', $this->user);
        $this->createQuestion($template, 'Your Name', 'string');
        $this->createQuestion($template, 'Your Age', 'int');
        $this->createQuestion($template, 'Agree?', 'checkbox');
        $this->createQuestion($template, 'Comments', 'text');
        $this->entityManager->flush(); // Ensure questions are persisted

        $client = static::createClient();
        $client->loginUser($this->user);

        $crawler = $client->request('GET', '/template/' . $template->getId() . '/fill');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Fill Out: Form Fill Template');

        $form = $crawler->selectButton('Submit Form')->form([
            'form_answer[stringAnswer1]' => 'John Doe',
            'form_answer[intAnswer2]' => 30,
            'form_answer[checkboxAnswer3]' => true,
            'form_answer[textAnswer4]' => 'Great template!',
        ]);

        $client->submit($form);

        $submittedForm = $this->entityManager->getRepository(FilledForm::class)->findOneBy(['template' => $template]);
        $this->assertNotNull($submittedForm);
        $this->assertResponseRedirects('/form/' . $submittedForm->getId());
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'Form submitted successfully!');
        $this->assertEquals('John Doe', $submittedForm->getStringAnswer1());
        $this->assertEquals(30, $submittedForm->getIntAnswer2());
        $this->assertTrue($submittedForm->isCheckboxAnswer3());
        $this->assertEquals('Great template!', $submittedForm->getTextAnswer4());
    }

    public function testShowFilledFormRequiresAccess(): void
    {
        $template = $this->createTemplate('Template with Filled Form');
        $userWhoFilled = $this->user;
        $userWhoCreatedTemplate = $this->user; // Same user for simplicity in this case

        $filledForm = new FilledForm();
        $filledForm->setTemplate($template);
        $filledForm->setUser($userWhoFilled);
        $filledForm->setStringAnswer1('Test Answer');
        $this->entityManager->persist($filledForm);
        $this->entityManager->flush();

        $client = static::createClient();

        // Non-logged in user
        $client->request('GET', '/form/' . $filledForm->getId());
        $this->assertResponseRedirects('/login');

        // Other user who neither filled nor created template
        $stranger = new User();
        $stranger->setEmail('stranger@example.com');
        $stranger->setPassword($this->passwordHasher->hashPassword($stranger, 'password'));
        $stranger->setRoles(['ROLE_USER']);
        $this->entityManager->persist($stranger);
        $this->entityManager->flush();
        $client->loginUser($stranger);

        $client->request('GET', '/form/' . $filledForm->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Admin should have access
        $client->loginUser($this->admin);
        $client->request('GET', '/form/' . $filledForm->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Filled Form: Template with Filled Form');
    }

    public function testDeleteFilledFormAsOwner(): void
    {
        $template = $this->createTemplate('Template for Deleting Form');
        $filledForm = new FilledForm();
        $filledForm->setTemplate($template);
        $filledForm->setUser($this->user);
        $this->entityManager->persist($filledForm);
        $this->entityManager->flush();

        $formId = $filledForm->getId();
        $client = static::createClient();
        $client->loginUser($this->user);

        $crawler = $client->request('POST', '/form/' . $formId . '/delete', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete' . $formId)->getValue(),
        ]);

        $this->assertResponseRedirects('/profile/' . $this->user->getId());
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'Form deleted successfully!');
        $this->assertNull($this->entityManager->getRepository(FilledForm::class)->find($formId));
    }

    public function testEditFilledFormAsOwner(): void
    {
        $template = $this->createTemplate('Template for Editing Form');
        $question = $this->createQuestion($template, 'Initial Answer', 'string');

        $filledForm = new FilledForm();
        $filledForm->setTemplate($template);
        $filledForm->setUser($this->user);
        $filledForm->setStringAnswer1('Original Value');
        $this->entityManager->persist($filledForm);
        $this->entityManager->flush();

        $client = static::createClient();
        $client->loginUser($this->user);

        $crawler = $client->request('GET', '/form/' . $filledForm->getId() . '/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorFieldValues([
            'form_answer[stringAnswer1]' => 'Original Value',
        ]);

        $form = $crawler->selectButton('Submit Form')->form([
            'form_answer[stringAnswer1]' => 'Updated Value',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/form/' . $filledForm->getId());
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Form answers updated successfully!');

        $updatedForm = $this->entityManager->getRepository(FilledForm::class)->find($filledForm->getId());
        $this->assertEquals('Updated Value', $updatedForm->getStringAnswer1());
    }

    public function testEditFilledFormWithOptimisticLockingConflict(): void
    {
        $template = $this->createTemplate('Template for Optimistic Lock');
        $question = $this->createQuestion($template, 'Lock Question', 'string');

        $filledForm = new FilledForm();
        $filledForm->setTemplate($template);
        $filledForm->setUser($this->user);
        $filledForm->setStringAnswer1('Initial Value');
        $this->entityManager->persist($filledForm);
        $this->entityManager->flush(); // Version = 1

        // Simulate client 1 fetching the form
        $client1 = static::createClient();
        $client1->loginUser($this->user);
        $crawler1 = $client1->request('GET', '/form/' . $filledForm->getId() . '/edit');
        $this->assertResponseIsSuccessful();
        $form1 = $crawler1->selectButton('Submit Form')->form([
            'form_answer[stringAnswer1]' => 'Client 1 Updated Value',
        ]);

        // Simulate client 2 updating the form first (increments version to 2)
        $filledForm->setStringAnswer1('Client 2 Updated Value');
        $this->entityManager->flush(); // Now version is 2

        // Client 1 tries to submit with old version (version 1)
        $client1->submit($form1);

        $this->assertResponseRedirects('/form/' . $filledForm->getId() . '/edit');
        $client1->followRedirect();

        $this->assertSelectorTextContains('.alert-error', 'These answers were modified by another user. Please review and try again.');
        // Verify that client 1's change was NOT applied
        $finalFormState = $this->entityManager->getRepository(FilledForm::class)->find($filledForm->getId());
        $this->assertEquals('Client 2 Updated Value', $finalFormState->getStringAnswer1());
        $this->assertEquals(2, $finalFormState->getVersion());
    }
}