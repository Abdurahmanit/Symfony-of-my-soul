<?php

namespace App\Tests\Functional\Form;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface; // Old interface
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // New interface
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class RegistrationFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        // Mock the password hasher if it's needed by the form for validation or transformation
        // For RegistrationFormType, the hasher is used AFTER form submission in the controller,
        // so it might not be strictly needed here unless you add custom data transformers.
        // However, for validator extension, it's good practice.
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();

        return [
            new ValidatorExtension($validator),
            // new PreloadedExtension([$this->createMock(UserPasswordEncoderInterface::class)], []), // For old Symfony versions
            // new PreloadedExtension([$this->createMock(UserPasswordHasherInterface::class)], []), // For Symfony 5.3+
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'plainPassword' => [
                'first' => 'password',
                'second' => 'password',
            ],
            'agreeTerms' => true,
        ];

        $user = new User();
        // $user->setEmail('initial@example.com'); // This is not needed as it will be mapped

        $form = $this->factory->create(RegistrationFormType::class, $user);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertNull($user->getPassword()); // Password is not set by the form type itself, but by the controller
        $this->assertTrue($form->get('agreeTerms')->getData());
    }

    public function testPasswordMismatchFailsValidation(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'plainPassword' => [
                'first' => 'password123',
                'second' => 'password456',
            ],
            'agreeTerms' => true,
        ];

        $user = new User();
        $form = $this->factory->create(RegistrationFormType::class, $user);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('plainPassword')->getErrors(true));
        $this->assertEquals('The password fields must match.', $form->get('plainPassword')->getErrors(true)[0]->getMessage());
    }

    public function testMissingEmailFailsValidation(): void
    {
        $formData = [
            'email' => '',
            'plainPassword' => [
                'first' => 'password',
                'second' => 'password',
            ],
            'agreeTerms' => true,
        ];

        $user = new User();
        $form = $this->factory->create(RegistrationFormType::class, $user);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('email')->getErrors(true));
        $this->assertEquals('Please enter an email', $form->get('email')->getErrors(true)[0]->getMessage());
    }

    public function testDisagreeTermsFailsValidation(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'plainPassword' => [
                'first' => 'password',
                'second' => 'password',
            ],
            'agreeTerms' => false,
        ];

        $user = new User();
        $form = $this->factory->create(RegistrationFormType::class, $user);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('agreeTerms')->getErrors(true));
        $this->assertEquals('You should agree to our terms.', $form->get('agreeTerms')->getErrors(true)[0]->getMessage());
    }
}