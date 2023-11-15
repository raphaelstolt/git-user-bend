<?php

namespace Stolt\GitUserBend\Tests\Persona;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Exceptions\AlreadyAliasedPersona;
use Stolt\GitUserBend\Exceptions\DuplicateAlias;
use Stolt\GitUserBend\Exceptions\NoDefinedPersonas;
use Stolt\GitUserBend\Exceptions\UnknownPersona;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\TestCase;

class StorageTest extends TestCase
{
    /**
     * @var string
     */
    private $storageFile;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
        $this->storageFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Storage::FILE_NAME;
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
    public function createsStorageFileForFirstPersona(): void
    {
        $storage = new Storage($this->storageFile);
        $firstPersona = new Persona('jo', 'John Doe', 'john.doe@example.org', 23);

        $addedAPersona = $storage->add($firstPersona);

        $expectedStorageFileContent = json_encode([$firstPersona]);

        $this->assertTrue($addedAPersona);
        $this->assertFileExists($this->storageFile);
        $this->assertJsonStringEqualsJsonString(
            (string) $expectedStorageFileContent,
            (string) file_get_contents($this->storageFile)
        );
    }

    #[Test]
    #[Group('unit')]
    public function addsPersonaToExistingPersonas(): void
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":23},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":11}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $storage = new Storage($this->storageFile);
        $additionalPersona = new Persona('sd', 'Sarah Doe', 'sarah.doe@example.org', 17);

        $addedAPersona = $storage->add($additionalPersona);

        $expectedStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":23},
 {"alias":"sd","name":"Sarah Doe","email":"sarah.doe@example.org","usage_frequency":17},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":11}]
CONTENT;

        $this->assertTrue($addedAPersona);
        $this->assertJsonStringEqualsJsonString(
            $expectedStorageContent,
            (string) file_get_contents($this->storageFile)
        );
    }

    #[Test]
    #[Group('unit')]
    public function addsAnAliasOnlyOnce(): void
    {
        $this->expectException(DuplicateAlias::class);
        $this->expectExceptionMessage("The alias 'jd' is already present.");

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":23},
 {"alias":"jd","name":"Jane Doe","email":"jane.doe@example.org","usage_frequency":11}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $storage = new Storage($this->storageFile);
        $additionalPersona = new Persona('jd', 'Jane Doe', 'jane.doe@example.org', 17);

        $storage->add($additionalPersona);
    }

    #[Test]
    #[Group('unit')]
    public function addsAPersonaOnlyOnce(): void
    {
        $this->expectException(AlreadyAliasedPersona::class);
        $this->expectExceptionMessage("The persona is already aliased to 'jado'.");

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":23},
 {"alias":"jado","name":"Jane Doe","email":"jane.doe@example.org","usage_frequency":11}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $storage = new Storage($this->storageFile);
        $additionalPersona = new Persona('jd', 'Jane Doe', 'jane.doe@example.org', 17);

        $storage->add($additionalPersona);
    }

    #[Test]
    #[Group('unit')]
    public function returnsEmptyCollectionOnNonExistentStorageFile(): void
    {
        $storage = new Storage($this->storageFile);
        $personas = $storage->all();

        $this->assertInstanceOf('Stolt\GitUserBend\Persona\Collection', $personas);
        $this->assertEquals(0, $personas->count());
    }

    #[Test]
    #[Group('unit')]
    public function returnsAllExistingPersonasOrderedByUsageFrequency(): void
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":5},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":52},
 {"alias":"sd","name":"Sarah Doe","email":"sarah.doe@example.org","usage_frequency":22}
]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);
        $storage = new Storage($this->storageFile);

        $personas = $storage->all();

        $this->assertInstanceOf('Stolt\GitUserBend\Persona\Collection', $personas);
        $this->assertEquals(3, $personas->count());

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
    public function removesExistingPersonaByAlias(): void
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":5},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":52},
 {"alias":"sd","name":"Sarah Doe","email":"sarah.doe@example.org","usage_frequency":22}
]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);
        $storage = new Storage($this->storageFile);

        $removedAPersona = $storage->remove('so');

        $personas = $storage->all();

        $this->assertTrue($removedAPersona);
        $this->assertEquals(2, $personas->count());

        $expectedPersonaAliasByIndex = ['sd', 'jo'];

        /** @var Persona $persona */
        foreach ($personas as $index => $persona) {
            $this->assertEquals(
                $expectedPersonaAliasByIndex[$index],
                $persona->getAlias()
            );
        }
    }

    #[Test]
    #[Group('unit')]
    public function lastPersonaRemovalDeletesStorageFile(): void
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":5}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);
        $storage = new Storage($this->storageFile);

        $storage->remove('jo');

        $this->assertFalse(\file_exists($this->storageFile));
    }

    #[Test]
    #[Group('unit')]
    public function removeOnUndefinedPersonasThrowsExpectedException(): void
    {
        $this->expectException(NoDefinedPersonas::class);
        $this->expectExceptionMessage('There are no defined personas.');

        $storage = new Storage($this->storageFile);
        $storage->remove('so');
    }

    #[Test]
    #[Group('unit')]
    public function removeOnUnknownPersonasThrowsExpectedException(): void
    {
        $this->expectException(UnknownPersona::class);
        $this->expectExceptionMessage("No known persona for alias 'so'.");

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":5},
 {"alias":"sd","name":"Sarah Doe","email":"sarah.doe@example.org","usage_frequency":22}
]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $storage = new Storage($this->storageFile);
        $storage->remove('so');
    }

    #[Test]
    #[Group('unit')]
    public function incrementsUsageFrequencyByAlias(): void
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":5},
 {"alias":"sd","name":"Sarah Doe","email":"sarah.doe@example.org","usage_frequency":22}
]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $storage = new Storage($this->storageFile);
        $storage->incrementUsageFrequency('sd');
        $storage->incrementUsageFrequency('sd');

        $sarah = $storage->all()->getByAlias('sd');

        $this->assertEquals(24, $sarah->getUsageFrequency());
    }

    #[Test]
    #[Group('unit')]
    public function incrementUsageFrequencyOnUndefinedPersonasThrowsExpectedException(): void
    {
        $this->expectException(NoDefinedPersonas::class);
        $this->expectExceptionMessage('There are no defined personas.');

        $storage = new Storage($this->storageFile);
        $storage->incrementUsageFrequency('so');
    }

    #[Test]
    #[Group('unit')]
    public function incrementUsageFrequencyOnUnknownPersonasThrowsExpectedException(): void
    {
        $this->expectException(UnknownPersona::class);
        $this->expectExceptionMessage("No known persona for alias 'so'.");

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":5},
 {"alias":"sd","name":"Sarah Doe","email":"sarah.doe@example.org","usage_frequency":22}
]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $storage = new Storage($this->storageFile);
        $storage->incrementUsageFrequency('so');
    }
}
