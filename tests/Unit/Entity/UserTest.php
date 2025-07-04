<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetId(): void
    {
        $user = new User();
        $this->assertNull($user->getId());
    }

    public function testEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';
        $user->setEmail($email);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->getUserIdentifier());
    }

    public function testPassword(): void
    {
        $user = new User();
        $password = 'hashedpassword';
        $user->setPassword($password);
        $this->assertEquals($password, $user->getPassword());
    }

    public function testRoles(): void
    {
        $user = new User();
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->setRoles(['ROLE_ADMIN']);
        $this->assertEquals(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());

        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']);
        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testIsBlocked(): void
    {
        $user = new User();
        $this->assertFalse($user->isBlocked());

        $user->setIsBlocked(true);
        $this->assertTrue($user->isBlocked());

        $user->setIsBlocked(false);
        $this->assertFalse($user->isBlocked());
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->setPassword('plainpassword');
        $user->eraseCredentials();
        $this->assertNotNull($user->getPassword());
    }
}