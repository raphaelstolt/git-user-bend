<?php

namespace Stolt\GitUserBend\Tests\Commands;

use \Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Commands\PairCommand;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Collection;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\CommandTester;
use Stolt\GitUserBend\Tests\TestCase;
use Symfony\Component\Console\Application;

class PairCommandTest extends TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @return Application
     */
    protected function getApplication(): Application
    {
        $application = new Application();
        $command = new PairCommand(
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
        $command = $this->application->find('pair');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => '/out/of/orbit',
            'aliases' => 'foo,boo',
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
        $command = $this->application->find('pair');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'aliases' => 'foo,boo',
        ]);

        $expectedDisplay = <<<CONTENT
Error: No Git repository in {$this->temporaryDirectory}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenOnlySinglePersonaAliasProvided(): void
    {
        $this->createTemporaryGitRepository();

        $command = $this->application->find('pair');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'aliases' => 'foo',
        ]);

        $expectedDisplay = <<<CONTENT
Error: Only provided a single persona alias.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenNoPersonasDefined(): void
    {
        $this->createTemporaryGitRepository();

        $command = $this->application->find('pair');
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

        $command = $this->application->find('pair');
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
    public function returnsExpectedWarningWhenSetPairPersonaFails(): void
    {
        $this->createTemporaryGitRepository();

        $repository = Mockery::mock('Stolt\GitUserBend\Git\Repository');
        $storage = Mockery::mock('Stolt\GitUserBend\Persona\Storage');
        $application = new Application();
        $command = new PairCommand($storage, $repository);
        $application->add($command);

        $personas = new Collection();
        $personas->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $personas->add(new Persona('so', 'Some One', 'some.one@example.org'));
        $storage->shouldReceive('all')->times(1)->andReturn($personas);

        $repository->shouldReceive('setDirectory')->times(1);
        $repository->shouldReceive('setUser')->times(1)->andReturn(false);
        $repository->shouldReceive('storePreviousUser')->times(1)->andReturn(true);

        $command = $application->find('pair');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'aliases' => 'jd, so',
        ]);

        $expectedDisplay = <<<CONTENT
Error: Failed to set pair John Doe and Some One <john.doe@example.org>.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function setsPairPersonasAndIncrementsUsageFrequencies(): void
    {
        $this->createTemporaryGitRepository(new User('John Doe', 'john.doe@example.org'));

        $existingStorageContent = <<<CONTENT
[{"alias":"jd","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('pair');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'aliases' => 'jd, so',
        ]);

        $expectedDisplay = <<<CONTENT
Set pair 'John Doe and Some One <john.doe@example.org>'.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();

        $this->assertEquals(12, $this->getUsageFrequency('jd'));
        $this->assertEquals(24, $this->getUsageFrequency('so'));
    }

    #[Test]
    #[Group('integration')]
    public function createsPairingBranchAsExpected(): void
    {
        $this->createTemporaryGitRepository(new User('John Doe', 'john.doe@example.org'));

        $existingStorageContent = <<<CONTENT
[{"alias":"jd","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('pair');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            'aliases' => 'jd, so',
            '--branch' => 'pairing-branch',
        ]);

        $expectedDisplay = <<<CONTENT
Set pair 'John Doe and Some One <john.doe@example.org>'.
Switched to a new branch pairing-branch.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }
}
