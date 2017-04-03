<?php

namespace Stolt\GitUserBend\Tests\Commands;

use Stolt\GitUserBend\Commands\PersonasCommand;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\TestCase;
use Symfony\Component\Console\Application;
use Stolt\GitUserBend\Tests\CommandTester;

class PersonasCommandTest extends TestCase
{
    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

    /**
     * @return \Symfony\Component\Console\Application
     */
    protected function getApplication()
    {
        $application = new Application();
        $application->add(new PersonasCommand(new Storage(STORAGE_FILE)));

        return $application;
    }

    /**
     * Set up test environment.
     */
    protected function setUp()
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
    protected function tearDown()
    {
        if (is_dir($this->temporaryDirectory)) {
            $this->removeDirectory($this->temporaryDirectory);
        }
    }

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenNoPersonasDefined()
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedPersonas()
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
        $this->assertTrue($commandTester->getStatusCode() == 0);
    }
}
