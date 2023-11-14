<?php

namespace Stolt\GitUserBend\Tests\Commands;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Commands\ExportCommand;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\CommandTester;
use Stolt\GitUserBend\Tests\TestCase;
use Symfony\Component\Console\Application;

class ExportCommandTest extends TestCase
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
        $command = new ExportCommand(
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
        $command = $this->application->find('export');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => '/out/of/orbit',
            'alias' => 'foo',
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
        $command = $this->application->find('export');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'alias' => 'foo',
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

        $command = $this->application->find('export');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jo',
        ]);

        $expectedDisplay = <<<CONTENT
Error: There are no defined personas.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenPersonaAliasNotKnown(): void
    {
        $this->createTemporaryGitRepository();

        $existingStorageContent = <<<CONTENT
[{"alias":"jd","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('export');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jo',
        ]);

        $expectedDisplay = <<<CONTENT
Error: No known persona for alias jo.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function exportsAPersonaAsExpected(): void
    {
        $this->createTemporaryGitRepository();

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('export');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jo',
        ]);

        $expectedGubDotfile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $expectedDisplay = <<<CONTENT
Exported persona aliased by jo into {$expectedGubDotfile}.

CONTENT;

        $expectedGubDotfileContent = <<<CONTENT
{
    "alias": "jo",
    "name": "John Doe",
    "email": "john.doe@example.org"
}

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringEqualsFile($expectedGubDotfile, $expectedGubDotfileContent);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenCreateGubDotfileFails(): void
    {
        $this->createTemporaryGitRepository();

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $repository = Mockery::mock('Stolt\GitUserBend\Git\Repository');
        $application = new Application();
        $command = new ExportCommand(new Storage(STORAGE_FILE), $repository);
        $application->add($command);

        $repository->shouldReceive('setDirectory')->times(1);
        $repository->shouldReceive('hasGubDotfile')->times(1)->andReturn(false);
        $repository->shouldReceive('createGubDotfile')->times(1)->andReturn(false);

        $command = $application->find('export');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jo',
        ]);

        $expectedDisplay = <<<CONTENT
Error: Failed to export persona jo ~ John Doe <john.doe@example.org>.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenExportPersonaAlreadyInGubDotfile(): void
    {
        $this->createTemporaryGitRepository();

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $gubFilePersona = new Persona('jo', 'John Doe', 'john.doe@example.org');
        $this->createTemporaryGubDotfile($gubFilePersona);

        $command = $this->application->find('export');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jo',
        ]);

        $expectedGubDotfile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $expectedDisplay = <<<CONTENT
Error: The persona {$gubFilePersona} is already present in {$expectedGubDotfile}.

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

        $command = $this->application->find('export');
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
