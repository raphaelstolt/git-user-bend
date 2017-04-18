<?php

namespace Stolt\GitUserBend\Tests;

use Stolt\GitUserBend\Exceptions\InvalidPersona;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Tests\TestCase;

class PersonaTest extends TestCase
{
    /**
     * Set up test environment.
     */
    protected function setUp()
    {
        $this->setUpTemporaryDirectory();
    }

    /**
     * Tear down test environment.
     *
     * @return void
     */
    protected function tearDown()
    {
        if (is_dir($this->temporaryDirectory)) {
            $this->removeDirectory($this->temporaryDirectory);
        }
    }

    /**
     * @test
     * @group unit
     */
    public function factorsGitUser()
    {
        $persona = new Persona('jo', 'John Doe', 'john.doe@example.org', 17);
        $gitUser = $persona->factorUser();

        $this->assertInstanceOf('Stolt\GitUserBend\Git\User', $gitUser);
        $this->assertEquals($persona->getName(), $gitUser->getName());
        $this->assertEquals($persona->getEmail(), $gitUser->getEmail());
    }

    /**
     * @test
     * @group unit
     */
    public function invalidPersonaThrowsExpectedException()
    {
        $this->expectException(InvalidPersona::class);
        $this->expectExceptionMessage("Persona has an invalid email address 'www.example.org'.");

        new Persona('jo', 'John Doe', 'www.example.org');
    }

    /**
     * @test
     * @group unit
     */
    public function invalidPersonaWithTooLongAliasThrowsExpectedException()
    {
        $this->expectException(InvalidPersona::class);
        $this->expectExceptionMessage('Persona alias is longer than 20 characters.');

        $tooLongAlias = str_repeat('a', Persona::MAX_ALIAS_LENGTH + 1);
        new Persona($tooLongAlias, 'John Doe', 'john.doe@example.org');
    }

    /**
     * @test
     * @group unit
     */
    public function johnIsConsideredAsAValidPersona()
    {
        $john = new Persona('jo', 'John Doe', 'john.doe@example.org', 17);

        $this->assertEquals('jo', $john->getAlias());
    }

    /**
     * @test
     * @group unit
     */
    public function personaWithPrivateGitHubEmailIsConsideredAsAValidPersona()
    {
        $john = new Persona('jo', 'John Doe', 'johndoe@users.noreply.github.com', 17);

        $this->assertEquals('jo', $john->getAlias());
    }

    /**
     * @test
     * @group unit
     */
    public function createsPersonaFromGitRepository()
    {
        $expectedRepositoryUser = new User('John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository($expectedRepositoryUser);

        $repository = new Repository($this->temporaryDirectory);

        $expectedPersona = new Persona(
            Persona::REPOSITORY_USER_ALIAS,
            'John Doe',
            'john.doe@example.org'
        );

        $this->assertEquals($expectedPersona, Persona::fromRepository($repository));
    }

    /**
     * @test
     * @group unit
     */
    public function cannotCreateInvalidPersonaFromMisconfiguredGitRepository()
    {
        $this->expectException(InvalidPersona::class);
        $this->expectExceptionMessage("Persona has an invalid email address 'abc'.");

        $invalidRepositoryUser = new User('John Doe', 'abc');

        $this->createTemporaryGitRepository($invalidRepositoryUser);

        $repository = new Repository($this->temporaryDirectory);
        Persona::fromRepository($repository);
    }

    /**
     * @test
     * @group unit
     */
    public function returnsExpectedJsonSerialization()
    {
        $persona = new Persona('jo', 'John Doe', 'john.doe@example.org', 17);

        $expectedPersona = [
            'alias' => $persona->getAlias(),
            'name' => $persona->getName(),
            'email' => $persona->getEmail(),
            'usage_frequency' => $persona->getUsageFrequency(),
        ];
        $expectedJson = json_encode($expectedPersona);

        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($persona));
    }
    /**
     * @test
     * @group unit
     */
    public function returnsExpectedGubFileSerialization()
    {
        $persona = new Persona('jo', 'John Doe', 'john.doe@example.org');

        $expectedPersona = [
            'alias' => $persona->getAlias(),
            'name' => $persona->getName(),
            'email' => $persona->getEmail(),
        ];

        $this->assertEquals(
            $expectedPersona,
            $persona->gubFileSerialize()
        );
    }

    /**
     * @test
     * @group unit
     */
    public function returnsExpectedStringRepresentation()
    {
        $persona = new Persona('jo', 'John Doe', 'john.doe@example.org', 17);

        $expectedString = $persona->getAlias() . ' ~ ' . $persona->getName()
            . ' <' . $persona->getEmail() . '>';

        $this->assertEquals($expectedString, $persona);
    }

    /**
     * @test
     * @group unit
     */
    public function returnsExpectedStringRepresentationForPersonaCreatedFromRepositoryUser()
    {
        $persona = new Persona(
            Persona::REPOSITORY_USER_ALIAS,
            'John Doe',
            'john.doe@example.org', 17
        );

        $expectedString = $persona->getName() . ' <' . $persona->getEmail() . '>';

        $this->assertEquals($expectedString, $persona);
    }

    /**
     * @test
     * @group unit
     */
    public function personasAreCompareable()
    {
        $john = new Persona(
            'john',
            'John Doe',
            'john.doe@example.org', 17
        );
        $jane = new Persona(
            'jane',
            'Jane Doe',
            'jane.doe@example.org', 17
        );

        $this->assertTrue($john->equals($john));
        $this->assertFalse($john->equals($jane));
    }
}
