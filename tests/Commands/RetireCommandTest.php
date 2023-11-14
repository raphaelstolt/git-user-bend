<?php

namespace Stolt\GitUserBend\Tests\Commands;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Commands\RetireCommand;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Collection;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Tests\CommandTester;
use Stolt\GitUserBend\Tests\TestCase;
use Symfony\Component\Console\Application;

class RetireCommandTest extends TestCase
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
        $application->add(new RetireCommand(new Storage(STORAGE_FILE)));

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
    public function retiresAPersonaAsExpected(): void
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('retire');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jo',
        ]);

        $expectedDisplay = <<<CONTENT
Retired persona jo ~ John Doe <john.doe@example.org>.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $commandTester->assertCommandIsSuccessful();
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenPersonaRetirementFails(): void
    {
        $storage = Mockery::mock('Stolt\GitUserBend\Persona\Storage');
        $application = new Application();
        $application->add(new RetireCommand($storage));

        $collection = new Collection();
        $collection->add(
            new Persona('jo', 'John Doe', 'john.doe@example.org')
        );

        $storage->shouldReceive('all')->times(1)->andReturn($collection);
        $storage->shouldReceive('remove')->times(1)->andReturn(false);

        $command = $application->find('retire');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'alias' => 'jo',
        ]);

        $expectedDisplay = <<<CONTENT
Error: Failed to retire persona jo ~ John Doe <john.doe@example.org>.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    #[Test]
    #[Group('integration')]
    public function returnsExpectedWarningWhenPersonaAliasNotKnown(): void
    {
        $existingStorageContent = <<<CONTENT
[{"alias":"jd","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('retire');
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
    public function returnsExpectedWarningWhenNoPersonasDefined(): void
    {
        $command = $this->application->find('retire');
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
    #[DataProvider('invalidAliases')]
    public function returnsExpectedWarningWhenAliasArgumentIsInvalid(string $invalidAlias, string $expectedError): void
    {
        $this->createTemporaryGitRepository();

        $existingStorageContent = <<<CONTENT
[{"alias":"jo","name":"John Doe","email":"john.doe@example.org","usage_frequency":11},
 {"alias":"so","name":"Some One","email":"some.one@example.org","usage_frequency":23}]
CONTENT;
        $this->createTemporaryStorageFile($existingStorageContent);

        $command = $this->application->find('retire');
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
