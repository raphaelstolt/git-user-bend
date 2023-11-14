<?php

namespace Stolt\GitUserBend\Tests\Persona;

use \RuntimeException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stolt\GitUserBend\Exceptions\AlreadyAliasedPersona;
use Stolt\GitUserBend\Exceptions\DuplicateAlias;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Pair;

class PairTest extends TestCase
{
    #[Test]
    #[Group('unit')]
    public function duplicateAliasAdditionThrowsExpectedException(): void
    {
        $this->expectException(DuplicateAlias::class);
        $this->expectExceptionMessage("The alias 'jd' is already present.");

        $pair = new Pair();
        $pair->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $pair->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
    }

    #[Test]
    #[Group('unit')]
    public function personaAlreadyAliasedThrowsExpectedException(): void
    {
        $this->expectException(AlreadyAliasedPersona::class);
        $this->expectExceptionMessage("The persona is already aliased to 'jd'.");

        $pair = new Pair();
        $pair->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $pair->add(new Persona('jodo', 'John Doe', 'john.doe@example.org'));
    }

    #[Test]
    #[Group('unit')]
    public function factorUserOnNonSetPersonasThrowsExpectedException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No personas to factor user from.');

        (new Pair())->factorUser();
    }

    #[Test]
    #[Group('unit')]
    public function factorUserOnSinglePersonaThrowsExpectedException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough personas to factor user from.');

        $pair = new Pair();
        $pair->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $pair->factorUser();
    }

    #[Test]
    #[Group('unit')]
    public function factorsExpectedUsers(): void
    {
        $pair = new Pair();
        $pair->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $pair->add(new Persona('ja', 'Jane Doe', 'jane.doe@example.org'));

        $expectedGitUser = new User(
            'John Doe and Jane Doe',
            'john.doe@example.org',
            'jd'
        );

        $this->assertEquals($expectedGitUser, $pair->factorUser());

        $pair->add(new Persona('sd', 'Sarah Doe', 'sarah.doe@example.org'));

        $expectedGitUser = new User(
            'John Doe, Jane Doe, and Sarah Doe',
            'john.doe@example.org',
            'jd'
        );

        $this->assertEquals($expectedGitUser, $pair->factorUser());
    }

    #[Test]
    #[Group('unit')]
    public function toStringCreatesExpectedFormat(): void
    {
        $pair = new Pair();
        $pair->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $pair->add(new Persona('ja', 'Jane Doe', 'jane.doe@example.org'));

        $expectedTwoPersonaString = 'John Doe and Jane Doe <john.doe@example.org>';

        $this->assertEquals($expectedTwoPersonaString, $pair->__toString());

        $pair->add(new Persona('sd', 'Sarah Doe', 'sarah.doe@example.org'));

        $expectedThreePersonaString = 'John Doe, Jane Doe, and Sarah Doe <john.doe@example.org>';

        $this->assertEquals($expectedThreePersonaString, $pair->__toString());
    }
}
