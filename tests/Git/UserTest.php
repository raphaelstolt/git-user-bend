<?php

namespace Stolt\GitUserBend\Tests\Git;

use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * @test
     * @group unit
     */
    public function canDetermineIfNameIsSet()
    {
        $this->assertTrue((new User('John'))->hasName());
        $this->assertFalse((new User())->hasName());
    }

    /**
     * @test
     * @group unit
     */
    public function canDetermineIfEmailIsSet()
    {
        $this->assertTrue((new User('John', 'john.doe@example.org'))->hasEmail());
        $this->assertFalse((new User('John'))->hasEmail());
    }

    /**
     * @test
     * @group unit
     */
    public function canDetermineIfPartialsAreSet()
    {
        $this->assertTrue((new User('John'))->partial());
        $this->assertTrue((new User(null, 'john.doe@example.org'))->partial());
        $this->assertFalse((new User('John', 'john.doe@example.org'))->partial());
    }

    /**
     * @test
     * @group unit
     */
    public function factorsExpectedPersona()
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
