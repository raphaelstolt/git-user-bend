<?php

namespace Stolt\GitUserBend\Tests\Persona;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stolt\GitUserBend\Exceptions\AlreadyAliasedPersona;
use Stolt\GitUserBend\Exceptions\DuplicateAlias;
use Stolt\GitUserBend\Exceptions\NoDefinedPersonas;
use Stolt\GitUserBend\Exceptions\UnknownPersona;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Collection;
use Stolt\GitUserBend\Persona\Pair;

class CollectionTest extends TestCase
{
    #[Test]
    #[Group('unit')]
    public function returnsExpectedJsonSerialization(): void
    {
        $collection = new Collection();
        $collection->add(new Persona('jo', 'John Doe', 'john.doe@example.org', 10));
        $collection->add(new Persona('jad', 'Jane Doe', 'jane.doe@example.org', 51));

        $expectedJson = <<<CONTENT
[{"alias":"jad","name":"Jane Doe","email":"jane.doe@example.org","usage_frequency":51},
 {"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":10}]
CONTENT;

        $this->assertJsonStringEqualsJsonString((string) $expectedJson, (string) json_encode($collection));
    }

    #[Test]
    #[Group('unit')]
    public function aliasLookupOnEmptyCollectionThrowsExpectedException(): void
    {
        $this->expectException(NoDefinedPersonas::class);
        $this->expectExceptionMessage('There are no defined personas.');

        $collection = (new Collection())->getByAlias('fo');
    }

    #[Test]
    #[Group('unit')]
    public function aliasLookupOnUnknownPersonaThrowsExpectedException(): void
    {
        $this->expectException(UnknownPersona::class);
        $this->expectExceptionMessage("No known persona for alias 'fo'.");

        $collection = new Collection();
        $collection->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $collection = $collection->getByAlias('fo');
    }

    #[Test]
    #[Group('unit')]
    public function duplicateAliasAdditionThrowsExpectedException(): void
    {
        $this->expectException(DuplicateAlias::class);
        $this->expectExceptionMessage("The alias 'jd' is already present.");

        $collection = new Collection();
        $collection->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $collection->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
    }

    #[Test]
    #[Group('unit')]
    public function personaAlreadyAliasedThrowsExpectedException(): void
    {
        $this->expectException(AlreadyAliasedPersona::class);
        $this->expectExceptionMessage("The persona is already aliased to 'jd'.");

        $collection = new Collection();
        $collection->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $collection->add(new Persona('jodo', 'John Doe', 'john.doe@example.org'));
    }

    #[Test]
    #[Group('unit')]
    public function removePersonaByAlias(): void
    {
        $collection = new Collection();
        $expectedSolePersona = new Persona('jd', 'John Doe', 'john.doe@example.org');
        $collection->add($expectedSolePersona);
        $collection->add(new Persona('jad', 'Jane Doe', 'jane.doe@example.org'));

        $collection->removeByAlias('jad');

        $this->assertEquals(1, $collection->count());
        $this->assertEquals($expectedSolePersona, $collection->getByAlias('jd'));
    }

    #[Test]
    #[Group('unit')]
    public function returnsPersonasOrderedByUsageFrequency(): void
    {
        $collection = new Collection();

        $john = new Persona('jd', 'John Doe', 'john.doe@example.org', 5);
        $jane = new Persona('jad', 'Jane Doe', 'jane.doe@example.org', 52);
        $sarah = new Persona('sd', 'Sarah Doe', 'sarah.doe@example.org', 22);

        $collection->add($john);
        $collection->add($jane);
        $collection->add($sarah);

        $personas = $collection->sorted();

        $expectedFrequencyByIndex = [52, 22, 5];

        /** @var Persona $persona */
        foreach ($personas as $index => $persona) {
            $this->assertEquals(
                $expectedFrequencyByIndex[$index],
                $persona->getUsageFrequency()
            );
        }
    }

    #[Test]
    #[Group('unit')]
    public function returnsPairForAliases(): void
    {
        $collection = new Collection();

        $john = new Persona('jd', 'John Doe', 'john.doe@example.org', 5);
        $jane = new Persona('jad', 'Jane Doe', 'jane.doe@example.org', 52);

        $collection->add($john);
        $collection->add($jane);

        $pair = $collection->pair(['jd', 'jad']);

        $this->assertInstanceOf(Pair::class, $pair);
        $this->assertEquals(2, $pair->count());
    }

    #[Test]
    #[Group('unit')]
    public function returnsTrueForAliasedPersona(): void
    {
        $collection = new Collection();

        $john = new Persona('jd', 'John Doe', 'john.doe@example.org', 5);
        $jane = new Persona('jad', 'Jane Doe', 'jane.doe@example.org', 52);

        $collection->add($john);
        $collection->add($jane);

        $aliasedPersona = new Persona(
            Persona::REPOSITORY_USER_ALIAS,
            'John Doe',
            'john.doe@example.org'
        );
        $this->assertTrue($collection->hasAliasedPersona($aliasedPersona));
    }

    #[Test]
    #[Group('unit')]
    public function returnsFalseForNonAliasedPersona(): void
    {
        $collection = new Collection();

        $john = new Persona('jd', 'John Doe', 'john.doe@example.org', 5);
        $jane = new Persona('jad', 'Jane Doe', 'jane.doe@example.org', 52);

        $collection->add($john);
        $collection->add($jane);

        $nonAliasedPersona = new Persona(
            Persona::REPOSITORY_USER_ALIAS,
            'Sarah Doe',
            'sarah.doe@example.org'
        );
        $this->assertFalse($collection->hasAliasedPersona($nonAliasedPersona));
    }

    #[Test]
    #[Group('unit')]
    public function returnsFalseForNonAliasedPersonaOnEmptyCollection(): void
    {
        $collection = new Collection();

        $nonAliasedPersona = new Persona(
            Persona::REPOSITORY_USER_ALIAS,
            'Sarah Doe',
            'sarah.doe@example.org'
        );
        $this->assertFalse($collection->hasAliasedPersona($nonAliasedPersona));
    }

    #[Test]
    #[Group('unit')]
    public function findsPersonaByNameAndEmail(): void
    {
        $collection = new Collection();

        $john = new Persona('jd', 'John Doe', 'john.doe@example.org', 5);
        $jane = new Persona('jad', 'Jane Doe', 'jane.doe@example.org', 52);

        $collection->add($john);
        $collection->add($jane);

        $this->assertEquals(
            $collection->getByAlias('jd'),
            $collection->getByNameAndEmail($john->getName(), $john->getEmail())
        );
    }

    #[Test]
    #[Group('unit')]
    public function lookupWithNameAndEmailOnEmptyCollectionThrowsExpectedException(): void
    {
        $this->expectException(NoDefinedPersonas::class);
        $this->expectExceptionMessage('There are no defined personas.');

        $collection = new Collection();
        $collection->getByNameAndEmail('Jane Doe', 'jane.doe@example.org');
    }

    #[Test]
    #[Group('unit')]
    public function lookupWithNameAndEmailWithNoMatchThrowsExpectedException(): void
    {
        $this->expectException(UnknownPersona::class);
        $expectedExceptionMessage = "No known persona for name 'Jane Doe' "
            . "and email 'jane.doe@example.org'.";
        $this->expectExceptionMessage($expectedExceptionMessage);

        $collection = new Collection();

        $john = new Persona('jd', 'John Doe', 'john.doe@example.org', 5);
        $jane = new Persona('sd', 'Sarah Doe', 'sarah.doe@example.org', 52);

        $collection->add($john);
        $collection->add($jane);

        $collection->getByNameAndEmail('Jane Doe', 'jane.doe@example.org');
    }
}
