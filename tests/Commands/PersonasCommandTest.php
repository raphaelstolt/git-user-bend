<?php

namespace Stolt\GitUserBend\Tests\Commands;

use \phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Commands\PersonasCommand;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\CommandTester;
use Stolt\GitUserBend\Tests\TestCase;
use Symfony\Component\Console\Application;

class PersonasCommandTest extends TestCase
{
    use PHPMock;

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
        $application->add(new PersonasCommand(new Storage(STORAGE_FILE)));

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
    public function returnsExpectedWarningWhenNoPersonasDefined(): void
    {
        $command = $this->application->find('personas');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $expectedDisplay = <<<CONTENT
Error: No personas defined yet. Use the add or import command to define some.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedPersonas(): void
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('personas');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $expectedDisplay = <<<CONTENT
+-------+----------+----------------------+-----------------+
| Alias | Name     | Email                | Usage frequency |
+-------+----------+----------------------+-----------------+
| so    | Some One | some.one@example.org | 23              |
| jo    | John Doe | john.doe@example.org | 11              |
+-------+----------+----------------------+-----------------+

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenNoStorageFilePresent(): void
    {
        $command = $this->application->find('personas');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--edit' => true,
        ]);

        $expectedDisplay = <<<CONTENT
Error: No personas defined yet therefore nothing to edit. Use the add or import command to define some.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedReturnCodeWhenOpeningAnEditor(): void
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $system = $this->getFunctionMock(
            'Stolt\GitUserBend\Commands',
            'system'
        );
        $system->expects($this->once())->willReturn(1);

        $command = $this->application->find('personas');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--edit' => true,
        ]);

        $this->assertEmpty($commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }
}
