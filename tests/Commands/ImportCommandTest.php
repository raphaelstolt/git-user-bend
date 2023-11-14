<?php

namespace Stolt\GitUserBend\Tests\Commands;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Commands\ImportCommand;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\CommandTester;
use Stolt\GitUserBend\Tests\TestCase;
use Symfony\Component\Console\Application;

class ImportCommandTest extends TestCase
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
        $command = new ImportCommand(
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
        $command = $this->application->find('import');
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
        $command = $this->application->find('import');
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
    public function returnsExpectedWarningWhenNoGubDotfilePresent(): void
    {
        $this->createTemporaryGitRepository();

        $command = $this->application->find('import');
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

        $temporaryGubFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        file_put_contents($temporaryGubFile, '{"ALIAS":"jd","NAME":"John Doe"}');

        $command = $this->application->find('import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            '--from-dotfile' => true,
        ]);

        $this->assertTrue(
            str_contains($commandTester->getDisplay(), 'Invalid ' . Repository::GUB_FILENAME . ' file content')
        );
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function importsAPersonaFromALocalGubDotfileIntoNonExistentStorage(): void
    {
        $personaToImport = new Persona('jd', 'John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository();
        $this->createTemporaryGubDotfile($personaToImport);

        $command = $this->application->find('import');
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
Imported persona {$personaToImport} from {$localGubDotfile}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenAliasFromGubDotfileAlreadyPresent(): void
    {
        $this->createTemporaryGitRepository();

        $existingPersona = new Persona('jo', 'John Doe', 'john.doe@example.org');
        $this->createTemporaryGubDotfile($existingPersona);

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            '--from-dotfile' => true,
        ]);

        $expectedDisplay = <<<CONTENT
Error: The alias jo is already present.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenPersonaFromGubDotfileAlreadyAliased(): void
    {
        $this->createTemporaryGitRepository();

        $existingPersona = new Persona('jd', 'John Doe', 'john.doe@example.org');
        $this->createTemporaryGubDotfile($existingPersona);

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            '--from-dotfile' => true,
        ]);

        $expectedDisplay = <<<CONTENT
Error: The persona is already aliased to jo.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenImportFromGubDotfileFails(): void
    {
        $this->createTemporaryGitRepository();
        $personaToImport = new Persona('jd', 'John Doe', 'john.doe@example.org');

        $repository = Mockery::mock('Stolt\GitUserBend\Git\Repository');
        $storage = Mockery::mock('Stolt\GitUserBend\Persona\Storage');
        $application = new Application();
        $command = new ImportCommand($storage, $repository);
        $application->add($command);

        $localGubDotfile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $repository->shouldReceive('setDirectory')->times(1);
        $repository->shouldReceive('getGubDotfilePath')->times(1)
          ->andReturn($localGubDotfile);
        $repository->shouldReceive('getPersonaFromGubDotfile')
          ->times(1)->andReturn($personaToImport);

        $storage->shouldReceive('add')->times(1)->andReturn(false);

        $command = $application->find('import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--from-dotfile' => true,
        ]);

        $expectedDisplay = <<<CONTENT
Error: Failed to import persona jd ~ John Doe <john.doe@example.org> from {$localGubDotfile}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function importsAPersonaFromALocalGitConfigurationIntoNonExistentStorage(): void
    {
        $userToImport = new User('John Doe', 'john.doe@example.org', 'jd');

        $this->createTemporaryGitRepository($userToImport);

        $command = $this->application->find('import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jd',
            'directory' => $this->temporaryDirectory,
        ]);

        $localDirectory = $this->temporaryDirectory;

        $expectedDisplay = <<<CONTENT
Imported persona from $localDirectory.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenImportFromLocalGitConfigurationFails(): void
    {
        $this->createTemporaryGitRepository();
        $personaToImport = new Persona('jd', 'John Doe', 'john.doe@example.org');

        $repository = Mockery::mock('Stolt\GitUserBend\Git\Repository');
        $storage = Mockery::mock('Stolt\GitUserBend\Persona\Storage');
        $application = new Application();
        $command = new ImportCommand($storage, $repository);
        $application->add($command);

        $repository->shouldReceive('setDirectory')->times(1);
        $repository->shouldReceive('getPersonaFromConfiguration')
          ->times(1)->andReturn($personaToImport);

        $storage->shouldReceive('add')->times(1)->andReturn(false);

        $command = $application->find('import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jd',
        ]);

        $expectedDisplay = <<<CONTENT
Error: Failed to import persona {$personaToImport} from {$this->temporaryDirectory}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenNoAliasForLocalGitConfigurationProvided(): void
    {
        $userToImport = new User('John Doe', 'john.doe@example.org', 'jd');

        $this->createTemporaryGitRepository($userToImport);

        $command = $this->application->find('import');
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
    public function returnsExpectedWarningWhenAliasAlreadyUsed(): void
    {
        $existingPersona = new Persona('jd', 'John Doe', 'john.doe@example.org');
        $this->createTemporaryGitRepository($existingPersona->factorUser());

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jo',
            'directory' => $this->temporaryDirectory,
        ]);

        $localDirectory = $this->temporaryDirectory;

        $expectedDisplay = <<<CONTENT
Error: The alias jo is already present.

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

        $command = $this->application->find('import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => $invalidAlias,
        ]);

        $expectedDisplay = <<<CONTENT
Error: {$expectedError}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }
}
