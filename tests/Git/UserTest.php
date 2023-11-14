<?php

namespace Stolt\GitUserBend\Tests\Git;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Tests\TestCase;

class UserTest extends TestCase
{
    #[Test]
    #[Group('unit')]
    public function canDetermineIfNameIsSet(): void
    {
        $this->assertTrue((new User('John'))->hasName());
        $this->assertFalse((new User())->hasName());
    }

    #[Test]
    #[Group('unit')]
    public function canDetermineIfEmailIsSet(): void
    {
        $this->assertTrue((new User('John', 'john.doe@example.org'))->hasEmail());
        $this->assertFalse((new User('John'))->hasEmail());
    }

    #[Test]
    #[Group('unit')]
    public function canDetermineIfPartialsAreSet(): void
    {
        $this->assertTrue((new User('John'))->partial());
        $this->assertTrue((new User(null, 'john.doe@example.org'))->partial());
        $this->assertFalse((new User('John', 'john.doe@example.org'))->partial());
    }

    #[Test]
    #[Group('unit')]
    public function factorsExpectedPersona(): void
    {
        $expectedPersona = new Persona(
            Persona::REPOSITORY_USER_ALIAS,
            'John Doe',
            'john.doe@example.org'
        );

        $user = new User(
            $expectedPersona->getName(),
            $expectedPersona->getEmail()
        );

        $this->assertEquals(
            $expectedPersona,
            $user->factorPersona()
        );
    }
}
