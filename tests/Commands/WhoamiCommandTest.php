<?php

namespace Stolt\GitUserBend\Tests\Commands;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Commands\WhoamiCommand;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Pair;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\CommandTester;
use Stolt\GitUserBend\Tests\TestCase;
use Symfony\Component\Console\Application;

class WhoamiCommandTest extends TestCase
{
    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

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

    /**
     * @return void
     */
    protected function setApplication(User $user = null): void
    {
        if ($user === null) {
            $this->createTemporaryGitRepository(new User('John Doe', 'john.doe@example.org'));
        } else {
            $this->createTemporaryGitRepository($user);
        }
        $application = new Application();
        $application->add(new WhoamiCommand(
            new Storage(STORAGE_FILE),
            new Repository(WORKING_DIRECTORY)
        ));

        $this->application = $application;
    }

    /**
     * @return \Symfony\Component\Console\Application
     */
    protected function getApplication(): Application
    {
        $application = new Application();
        $application->add(new WhoamiCommand(
            new Storage(STORAGE_FILE),
            new Repository(WORKING_DIRECTORY)
        ));

        return $application;
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedUnaliasedPersona(): void
    {
        $this->setApplication();

        $command = $this->application->find('whoami');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => WORKING_DIRECTORY,
        ]);

        $expectedDisplay = <<<CONTENT
The current unaliased persona is John Doe <john.doe@example.org> on branch main.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedAliasedPersona(): void
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);
        $this->createTemporaryGitRepository();

        $this->setApplication();

        $command = $this->application->find('whoami');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => WORKING_DIRECTORY,
        ]);

        $expectedDisplay = <<<CONTENT
The current persona is jo ~ John Doe <john.doe@example.org> on branch main.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedPair(): void
    {
        $pair = new Pair();
        $pair->add(new Persona('ja', 'Jane Doe', 'jane.doe@example.org'));
        $pair->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $pair->add(new Persona('sa', 'Sarah Doe', 'sarah.doe@example.org'));

        $this->setApplication($pair->factorUser());

        $command = $this->application->find('whoami');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => WORKING_DIRECTORY,
        ]);

        $expectedDisplay = <<<CONTENT
The current pair is Jane Doe, John Doe, and Sarah Doe <jane.doe@example.org>.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenProvidedDirectoryDoesNotExist(): void
    {
        $application = $this->getApplication();

        $command = $application->find('whoami');
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
        $application = $this->getApplication();

        $command = $application->find('whoami');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => WORKING_DIRECTORY,
        ]);

        $expectedDisplay = <<<CONTENT
Error: No Git repository in {$this->temporaryDirectory}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }
}
