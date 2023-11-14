<?php

namespace Stolt\GitUserBend\Tests\Commands;

use \Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Commands\UseCommand;
use Stolt\GitUserBend\Exceptions\UnresolvablePersona;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Collection;
use Stolt\GitUserBend\Persona\Pair;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\CommandTester;
use Stolt\GitUserBend\Tests\TestCase;
use Symfony\Component\Console\Application;

class UseCommandTest extends TestCase
{
    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

    /**
     * @return \Symfony\Component\Console\Application
     */
    protected function getApplication(): Application
    {
        $application = new Application();
        $command = new UseCommand(
            new Storage(STORAGE_FILE),
            new Repository($this->temporaryDirectory)
        );

        $application->add($command);

        return $application;
    }

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();

        if (!defined('WORKING_DIRECTORY')) {
            define('WORKING_DIRECTORY', $this->temporaryDirectory);
        }

        if (!defined('HOME_DIRECTORY')) {
            define('HOME_DIRECTORY', $this->temporaryDirectory);
        }

        if (!defined('STORAGE_FILE')) {
            define(
                'STORAGE_FILE',
                HOME_DIRECTORY . DIRECTORY_SEPARATOR . Storage::FILE_NAME
            );
        }

        $this->application = $this->getApplication();
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
    #[Group('integration')]
    public function returnsExpectedWarningWhenProvidedDirectoryDoesNotExist(): void
    {
        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => '/out/of/orbit',
        ]);

        $expectedDisplay = <<<CONTENT
Error: The directory /out/of/orbit doesn't exist.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenProvidedDirectoryIsNotAGitRepository(): void
    {
        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
        ]);

        $expectedDisplay = <<<CONTENT
Error: No Git repository in {$this->temporaryDirectory}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenNoPersonasDefined(): void
    {
        $this->createTemporaryGitRepository();

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'alias' => 'jo',
        ]);

        $expectedDisplay = <<<CONTENT
Error: There are no defined personas.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'aliases' => 'foo, bar',
        ]);

        $expectedDisplay = <<<CONTENT
Error: There are no defined personas.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenUnknownPersonaAliasProvided(): void
    {
        $this->createTemporaryGitRepository();

        $existingStorageContent = <<<CONTENT
[{"alias":"jd","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'alias' => 'jo',
        ]);

        $expectedDisplay = <<<CONTENT
Error: No known persona for alias jo.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'aliases' => 'jd, bar',
        ]);

        $expectedDisplay = <<<CONTENT
Error: No known persona for alias bar.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenOnlySinglePersonaAliasProvided(): void
    {
        $this->createTemporaryGitRepository();

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'aliases' => 'jo',
        ]);

        $expectedDisplay = <<<CONTENT
Error: Only provided a single persona alias.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenNoGubDotfilePresent(): void
    {
        $this->createTemporaryGitRepository();

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            '--from-dotfile' => true,
        ]);

        $expectedDisplay = <<<CONTENT
Error: No .gub file present in {$this->temporaryDirectory}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenGubDotfileInvalid(): void
    {
        $this->createTemporaryGitRepository();

        $gubFilename = Repository::GUB_FILENAME;
        $temporaryGubFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . $gubFilename;

        file_put_contents($temporaryGubFile, '{"ALIAS":"jd","NAME":"John Doe"}');

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            '--from-dotfile' => true,
        ]);

        $expectedDisplay = <<<CONTENT
Error: Invalid {$gubFilename} file content unable to create a persona from it.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenPersonaFromGubDotfileAlreadyInUse(): void
    {
        $persona = new Persona('jd', 'John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository($persona->factorUser());
        $this->createTemporaryGubDotfile($persona);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            '--from-dotfile' => true,
        ]);

        $expectedDisplay = <<<CONTENT
Error: Persona {$persona} already in use.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function usesPersonaFromGubDotfile()
    {
        $persona = new Persona('jd', 'John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository();
        $this->createTemporaryGubDotfile($persona);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            '--from-dotfile' => true,
        ]);

        $localGubDotfile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $expectedDisplay = <<<CONTENT
Set {$persona} from {$localGubDotfile}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }

    #[Test]
    #[Group('integration')]
    public function usesPersonaFromGubDotfileAndIncrementsUsageFrequencyIfInStorage(): void
    {
        $persona = new Persona('jd', 'John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository();
        $this->createTemporaryGubDotfile($persona);

        $existingStorageContent = <<<CONTENT
[{"alias":"jd","name":"John Doe","email":"john.doe@example.org","usage_frequency":12},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            '--from-dotfile' => true,
        ]);

        $localGubDotfile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $expectedDisplay = <<<CONTENT
Set {$persona} from {$localGubDotfile}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();

        $this->assertEquals(13, $this->getUsageFrequency($persona->getAlias()));
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenUseFromGubDotfileFails(): void
    {
        $this->createTemporaryGitRepository();

        $personaToUse = new Persona('jd', 'John Doe', 'john.doe@example.org');
        $personaCurrentlyUsed = new Persona('ja', 'Jane Doe', 'jane.doe@example.org');

        $repository = Mockery::mock('Stolt\GitUserBend\Git\Repository');
        $storage = Mockery::mock('Stolt\GitUserBend\Persona\Storage');
        $application = new Application();
        $command = new UseCommand($storage, $repository);
        $application->add($command);

        $localGubDotfile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $repository->shouldReceive('setDirectory')->times(1);
        $repository->shouldReceive('getGubDotfilePath')->times(1)
          ->andReturn($localGubDotfile);
        $repository->shouldReceive('getPersonaFromConfiguration')
          ->times(1)->andReturn($personaCurrentlyUsed);
        $repository->shouldReceive('getPersonaFromGubDotfile')
          ->times(1)->andReturn($personaToUse);

        $repository->shouldReceive('setUser')->times(1)->andReturn(false);

        $command = $application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--from-dotfile' => true,
        ]);

        $expectedDisplay = <<<CONTENT
Error: Failed to set persona jd ~ John Doe <john.doe@example.org> from {$localGubDotfile}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenAliasArgumentNotProvided(): void
    {
        $this->createTemporaryGitRepository();

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
        ]);

        $expectedDisplay = <<<CONTENT
Error: Required alias argument not provided.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    #[DataProvider('invalidAliases')]
    public function returnsExpectedWarningWhenAliasArgumentIsInvalid(string $invalidAlias, string $expectedError): void
    {
        $this->createTemporaryGitRepository();

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'alias' => $invalidAlias,
        ]);

        $expectedDisplay = <<<CONTENT
Error: {$expectedError}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenAliasAlreadyInUse(): void
    {
        $persona = new Persona('jo', 'John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository($persona->factorUser());

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'alias' => $persona->getAlias(),
        ]);

        $expectedDisplay = <<<CONTENT
Error: Persona {$persona} already in use.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function usesPersonaAndIncrementsUsageFrequency(): void
    {
        $persona = new Persona('jo', 'John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository();

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'alias' => $persona->getAlias(),
        ]);

        $expectedDisplay = <<<CONTENT
Set persona {$persona}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();

        $this->assertEquals(12, $this->getUsageFrequency($persona->getAlias()));
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenUseOfAliasFails(): void
    {
        $this->createTemporaryGitRepository();

        $personaToUse = new Persona('jd', 'John Doe', 'john.doe@example.org');

        $repository = Mockery::mock('Stolt\GitUserBend\Git\Repository');
        $storage = Mockery::mock('Stolt\GitUserBend\Persona\Storage');
        $application = new Application();
        $command = new UseCommand($storage, $repository);
        $application->add($command);

        $personas = new Collection();
        $personas->add($personaToUse);

        $storage->shouldReceive('all')->times(1)->andReturn($personas);
        $repository->shouldReceive('setDirectory')->times(1);
        $repository->shouldReceive('getPersonaFromConfiguration')
          ->times(1)->andThrow(new UnresolvablePersona());
        $repository->shouldReceive('setUser')->times(1)->andReturn(false);

        $command = $application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => $personaToUse->getAlias(),
        ]);

        $expectedDisplay = <<<CONTENT
Error: Failed to set persona jd ~ John Doe <john.doe@example.org>.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenPairAlreadyInUse(): void
    {
        $persona = new Persona('jo', 'John Doe and Jane Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository($persona->factorUser());

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"ja","name":"Jane Doe","email":"jane.doe@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'aliases' => 'jo,ja',
        ]);

        $expectedDisplay = <<<CONTENT
Error: Pair {$persona} already in use.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenAliasAndAliasesArgumentsAreUsedTogether(): void
    {
        $this->createTemporaryGitRepository();

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"ja","name":"Jane Doe","email":"jane.doe@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'alias' => 'jo',
            'aliases' => 'jo,ja',
        ]);

        $expectedDisplay = <<<CONTENT
Error: The alias and aliases arguments can't be used together.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function usesPairAndIncrementsUsageFrequencies(): void
    {
        $john = new Persona('jo', 'John Doe', 'john.doe@example.org');
        $jane = new Persona('ja', 'Jane Doe', 'jane.doe@example.org');

        $this->createTemporaryGitRepository();

        $pair = new Pair();
        $pair->add($john);
        $pair->add($jane);

        $pair = $pair->factorUser()->factorPersona();

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"ja","name":"Jane Doe","email":"jane.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'aliases' => 'jo,ja',
        ]);

        $expectedDisplay = <<<CONTENT
Set pair {$pair}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();

        $this->assertEquals(12, $this->getUsageFrequency($john->getAlias()));
        $this->assertEquals(24, $this->getUsageFrequency($jane->getAlias()));
    }
}
