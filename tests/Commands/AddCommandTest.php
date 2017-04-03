<?php

namespace Stolt\GitUserBend\Tests\Commands;

use Mockery;
use Stolt\GitUserBend\Commands\AddCommand;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\CommandTester;
use Stolt\GitUserBend\Tests\TestCase;
use Symfony\Component\Console\Application;

class AddCommandTest extends TestCase
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
        $application->add(new AddCommand(new Storage(STORAGE_FILE)));

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
    public function addsAPersonaAsExpected()
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('add');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jd',
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.org',
        ]);

        $expectedDisplay = <<<CONTENT
Added persona jd ~ Jane Doe <jane.doe@example.org>.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 0);
    }

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenProvidedAliasIsTooLong()
    {
        $maxAliasLength = Persona::MAX_ALIAS_LENGTH;
        $alias = str_repeat('x', $maxAliasLength + 1);

        $command = $this->application->find('add');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => $alias,
            'name' => 'John Doe',
            'email' => 'john.doe@example.org',
        ]);

        $expectedDisplay = <<<CONTENT
Error: The provided alias {$alias} is longer than {$maxAliasLength} characters.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenProvidedEmailIsInvalid()
    {
        $email = 1234;

        $command = $this->application->find('add');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jd',
            'name' => 'John Doe',
            'email' => $email,
        ]);

        $expectedDisplay = <<<CONTENT
Error: The provided email address {$email} is invalid.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenPersonaAlreadyAliased()
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('add');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jd',
            'name' => 'John Doe',
            'email' => 'john.doe@example.org',
        ]);

        $expectedDisplay = <<<CONTENT
Error: The persona is already aliased to jo.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenPersonaAliasAlreadyPresent()
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('add');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jo',
            'name' => 'John Doe',
            'email' => 'john.doe@example.org',
        ]);

        $expectedDisplay = <<<CONTENT
Error: The alias jo is already present.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenPersonaAdditionFails()
    {
        $storage = Mockery::mock('Stolt\GitUserBend\Persona\Storage');
        $application = new Application();
        $application->add(new AddCommand($storage));

        $storage->shouldReceive('add')->times(1)->andReturn(false);

        $command = $application->find('add');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jd',
            'name' => 'John Doe',
            'email' => 'john.doe@example.org',
        ]);

        $expectedDisplay = <<<CONTENT
Error: Failed to add persona jd ~ John Doe <john.doe@example.org>.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }
}
