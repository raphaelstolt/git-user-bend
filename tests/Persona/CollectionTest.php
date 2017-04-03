<?php

namespace Stolt\GitUserBend\Tests\Persona;

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
    /**
     * @test
     * @group unit
     */
    public function returnsExpectedJsonSerialization()
    {
        $collection = new Collection();
        $collection->add(new Persona('jo', 'John Doe', 'john.doe@example.org', 10));
        $collection->add(new Persona('jad', 'Jane Doe', 'jane.doe@example.org', 51));

        $expectedJson = <<<CONTENT
[{"alias":"jad","name":"Jane Doe","email":"jane.doe@example.org","usage_frequency":51},
 {"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":10}]
CONTENT;

        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($collection));
    }

    /**
     * @test
     * @group unit
     */
    public function aliasLookupOnEmptyCollectionThrowsExpectedException()
    {
        $this->expectException(NoDefinedPersonas::class);
        $this->expectExceptionMessage('There are no defined personas.');

        $collection = (new Collection())->getByAlias('fo');
    }

    /**
     * @test
     * @group unit
     */
    public function aliasLookupOnUnknownPersonaThrowsExpectedException()
    {
        $this->expectException(UnknownPersona::class);
        $this->expectExceptionMessage("No known persona for alias 'fo'.");

        $collection = new Collection();
        $collection->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $collection = $collection->getByAlias('fo');
    }

    /**
     * @test
     * @group unit
     */
    public function duplicateAliasAdditionThrowsExpectedException()
    {
        $this->expectException(DuplicateAlias::class);
        $this->expectExceptionMessage("The alias 'jd' is already present.");

        $collection = new Collection();
        $collection->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $collection->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
    }

    /**
     * @test
     * @group unit
     */
    public function personaAlreadyAliasedThrowsExpectedException()
    {
        $this->expectException(AlreadyAliasedPersona::class);
        $this->expectExceptionMessage("The persona is already aliased to 'jd'.");

        $collection = new Collection();
        $collection->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $collection->add(new Persona('jodo', 'John Doe', 'john.doe@example.org'));
    }

    /**
     * @test
     * @group unit
     */
    public function removePersonaByAlias()
    {
        $collection = new Collection();
        $expectedSolePersona = new Persona('jd', 'John Doe', 'john.doe@example.org');
        $collection->add($expectedSolePersona);
        $collection->add(new Persona('jad', 'Jane Doe', 'jane.doe@example.org'));

        $collection->removeByAlias('jad');

        $this->assertEquals(1, $collection->count());
        $this->assertEquals($expectedSolePersona, $collection->getByAlias('jd'));
    }

    /**
     * @test
     * @group unit
     */
    public function returnsPersonasOrderedByUsageFrequency()
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

        foreach ($personas as $index => $persona) {
            $this->assertEquals(
                $expectedFrequencyByIndex[$index],
                $persona->getUsageFrequency()
            );
        }
    }

    /**
     * @test
     * @group unit
     */
    public function returnsPairForAliases()
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

    /**
     * @test
     * @group unit
     */
    public function returnsTrueForAliasedPersona()
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

    /**
     * @test
     * @group unit
     */
    public function returnsFalseForNonAliasedPersona()
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

    /**
     * @test
     * @group unit
     */
    public function returnsFalseForNonAliasedPersonaOnEmptyCollection()
    {
        $collection = new Collection();

        $nonAliasedPersona = new Persona(
            Persona::REPOSITORY_USER_ALIAS,
            'Sarah Doe',
            'sarah.doe@example.org'
        );
        $this->assertFalse($collection->hasAliasedPersona($nonAliasedPersona));
    }

    /**
     * @test
     * @group unit
     */
    public function findsPersonaByNameAndEmail()
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

    /**
     * @test
     * @group unit
     */
    public function lookupWithNameAndEmailOnEmptyCollectionThrowsExpectedException()
    {
        $this->expectException(NoDefinedPersonas::class);
        $this->expectExceptionMessage('There are no defined personas.');

        $collection = new Collection();
        $collection->getByNameAndEmail('Jane Doe', 'jane.doe@example.org');
    }

    /**
     * @test
     * @group unit
     */
    public function lookupWithNameAndEmailWithNoMatchThrowsExpectedException()
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
