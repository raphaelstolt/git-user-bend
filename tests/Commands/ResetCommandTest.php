<?php

namespace Stolt\GitUserBend\Tests\Commands;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Commands\ResetCommand;
use Stolt\GitUserBend\Commands\UseCommand;
use Stolt\GitUserBend\Exceptions\InvalidPersona;
use Stolt\GitUserBend\Exceptions\UnresolvablePersona;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Helpers\Str as OsHelper;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\CommandTester;
use Stolt\GitUserBend\Tests\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class ResetCommandTest extends TestCase
{
    /**
     * @var Application
     */
    private Application $application;

    /**
     * @return Application
     */
    protected function getApplication(): Application
    {
        $application = new Application();
        $command = new UseCommand(
            new Storage(STORAGE_FILE),
            new Repository($this->temporaryDirectory)
        );
        $resetCommand = new ResetCommand(
            new Repository($this->temporaryDirectory)
        );

        $application->add($command);
        $application->add($resetCommand);

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
        $command = $this->application->find('reset');
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
        $command = $this->application->find('reset');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
        ]);

        $expectedDisplay = <<<CONTENT
Error: No Git repository in {$this->temporaryDirectory}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == Command::FAILURE);
    }

    /**
     * @throws UnresolvablePersona
     * @throws InvalidPersona
     */
    #[Test]
    #[Group('integration')]
    public function resetsGitConfigToFormerUser(): void
    {
        /*if ((new OsHelper())->isWindows()) {
            $this->markTestSkipped('Skipping test on Windows systems');
        }*/

        $this->createTemporaryGitRepository(new User('John Doe', 'test@test.org'));

        chdir($this->temporaryDirectory);

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
            'alias' => 'jd',
        ]);

        $commandTester->assertCommandIsSuccessful();

        $command = $this->application->find('reset');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
        ]);

        $expectedDisplay = <<<CONTENT
Reset user config to 'John Doe <test@test.org>'.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();

        $repository = new Repository($this->temporaryDirectory);
        $personaFromConfig = $repository->getPersonaFromConfiguration();
        $this->assertSame('John Doe', $personaFromConfig->getName());
        $this->assertSame('test@test.org', $personaFromConfig->getEmail());

        $this->expectException(UnresolvablePersona::class);
        $repository->getFormerPersonaFromConfiguration();
    }
}
