<?php

namespace Stolt\GitUserBend\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
    }

    /**
     * Tear down test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (is_dir($this->temporaryDirectory)) {
            $this->removeDirectory($this->temporaryDirectory);
        }
    }

    #[Test]
    #[Group('unit')]
    public function factorsGitUser(): void
    {
        $persona = new Persona('jo', 'John Doe', 'john.doe@example.org', 17);
        $gitUser = $persona->factorUser();

        $this->assertInstanceOf('Stolt\GitUserBend\Git\User', $gitUser);
        $this->assertEquals($persona->getName(), $gitUser->getName());
        $this->assertEquals($persona->getEmail(), $gitUser->getEmail());
    }

    #[Test]
    #[Group('unit')]
    public function invalidPersonaThrowsExpectedException(): void
    {
        $this->expectException(InvalidPersona::class);
        $this->expectExceptionMessage("Persona has an invalid email address 'www.example.org'.");

        new Persona('jo', 'John Doe', 'www.example.org');
    }

    #[Test]
    #[Group('unit')]
    public function invalidPersonaWithTooLongAliasThrowsExpectedException(): void
    {
        $this->expectException(InvalidPersona::class);
        $this->expectExceptionMessage('Persona alias is longer than 20 characters.');

        $tooLongAlias = str_repeat('a', Persona::MAX_ALIAS_LENGTH + 1);
        new Persona($tooLongAlias, 'John Doe', 'john.doe@example.org');
    }

    #[Test]
    #[Group('unit')]
    public function johnIsConsideredAsAValidPersona(): void
    {
        $john = new Persona('jo', 'John Doe', 'john.doe@example.org', 17);

        $this->assertEquals('jo', $john->getAlias());
    }

    #[Test]
    #[Group('unit')]
    public function personaWithPrivateGitHubEmailIsConsideredAsAValidPersona(): void
    {
        $john = new Persona('jo', 'John Doe', 'johndoe@users.noreply.github.com', 17);

        $this->assertEquals('jo', $john->getAlias());
    }

    #[Test]
    #[Group('unit')]
    public function createsPersonaFromGitRepository(): void
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

    #[Test]
    #[Group('unit')]
    public function cannotCreateInvalidPersonaFromMisconfiguredGitRepository(): void
    {
        $this->expectException(InvalidPersona::class);
        $this->expectExceptionMessage("Persona has an invalid email address 'abc'.");

        $invalidRepositoryUser = new User('John Doe', 'abc');

        $this->createTemporaryGitRepository($invalidRepositoryUser);

        $repository = new Repository($this->temporaryDirectory);
        Persona::fromRepository($repository);
    }

    #[Test]
    #[Group('unit')]
    public function returnsExpectedJsonSerialization(): void
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
    #[Test]
    #[Group('unit')]
    public function returnsExpectedGubFileSerialization(): void
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

    #[Test]
    #[Group('unit')]
    public function returnsExpectedStringRepresentation(): void
    {
        $persona = new Persona('jo', 'John Doe', 'john.doe@example.org', 17);

        $expectedString = $persona->getAlias() . ' ~ ' . $persona->getName()
            . ' <' . $persona->getEmail() . '>';

        $this->assertEquals($expectedString, $persona);
    }

    #[Test]
    #[Group('unit')]
    public function returnsExpectedStringRepresentationForPersonaCreatedFromRepositoryUser(): void
    {
        $persona = new Persona(
            Persona::REPOSITORY_USER_ALIAS,
            'John Doe',
            'john.doe@example.org',
            17
        );

        $expectedString = $persona->getName() . ' <' . $persona->getEmail() . '>';

        $this->assertEquals($expectedString, $persona);
    }

    #[Test]
    #[Group('unit')]
    public function personasAreCompareable(): void
    {
        $john = new Persona(
            'john',
            'John Doe',
            'john.doe@example.org',
            17
        );
        $jane = new Persona(
            'jane',
            'Jane Doe',
            'jane.doe@example.org',
            17
        );

        $this->assertTrue($john->equals($john));
        $this->assertFalse($john->equals($jane));
    }
}
