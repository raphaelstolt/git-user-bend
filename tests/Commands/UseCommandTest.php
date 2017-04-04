<?php

namespace Stolt\GitUserBend\Tests\Commands;

use \Mockery;
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
    protected function getApplication()
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
    public function returnsExpectedWarningWhenProvidedDirectoryDoesNotExist()
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenProvidedDirectoryIsNotAGitRepository()
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenNoPersonasDefined()
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenUnknownPersonaAliasProvided()
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenOnlySinglePersonaAliasProvided()
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenNoGubDotFilePresent()
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenGubDotFileInvalid()
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenPersonaFromGubDotFileAlreadyInUse()
    {
        $persona = new Persona('jd', 'John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository($persona->factorUser());
        $this->createTemporaryGubDotFile($persona);

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

    /**
     * @test
     * @group integration
     */
    public function usesPersonaFromGubDotFile()
    {
        $persona = new Persona('jd', 'John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository();
        $this->createTemporaryGubDotFile($persona);

        $command = $this->application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'directory' => $this->temporaryDirectory,
            '--from-dotfile' => true,
        ]);

        $localGubDotFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $expectedDisplay = <<<CONTENT
Set {$persona} from {$localGubDotFile}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 0);
    }

    /**
     * @test
     * @group integration
     */
    public function usesPersonaFromGubDotFileAndIncrementsUsageFrequencyIfInStorage()
    {
        $persona = new Persona('jd', 'John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository();
        $this->createTemporaryGubDotFile($persona);

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

        $localGubDotFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $expectedDisplay = <<<CONTENT
Set {$persona} from {$localGubDotFile}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 0);

        $this->assertEquals(13, $this->getUsageFrequency($persona->getAlias()));
    }

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenUseFromGubDotFileFails()
    {
        $this->createTemporaryGitRepository();

        $personaToUse = new Persona('jd', 'John Doe', 'john.doe@example.org');
        $personaCurrentlyUsed = new Persona('ja', 'Jane Doe', 'jane.doe@example.org');

        $repository = Mockery::mock('Stolt\GitUserBend\Git\Repository');
        $storage = Mockery::mock('Stolt\GitUserBend\Persona\Storage');
        $application = new Application();
        $command = new UseCommand($storage, $repository);
        $application->add($command);

        $localGubDotFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $repository->shouldReceive('setDirectory')->times(1);
        $repository->shouldReceive('getGubDotFilePath')->times(1)
          ->andReturn($localGubDotFile);
        $repository->shouldReceive('getPersonaFromConfiguration')
          ->times(1)->andReturn($personaCurrentlyUsed);
        $repository->shouldReceive('getPersonaFromGubDotFile')
          ->times(1)->andReturn($personaToUse);

        $repository->shouldReceive('setUser')->times(1)->andReturn(false);

        $command = $application->find('use');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--from-dotfile' => true,
        ]);

        $expectedDisplay = <<<CONTENT
Error: Failed to set persona jd ~ John Doe <john.doe@example.org> from {$localGubDotFile}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 1);
    }

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenAliasArgumentNotProvided()
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

    /**
     * @test
     * @group integration
     * @param string $invalidAlias
     * @param string $expectedError
     * @dataProvider invalidAliases
     */
    public function returnsExpectedWarningWhenAliasArgumentIsInvalid($invalidAlias, $expectedError)
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenAliasAlreadyInUse()
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

    /**
     * @test
     * @group integration
     */
    public function usesPersonaAndIncrementsUsageFrequency()
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

        $localGubDotFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $expectedDisplay = <<<CONTENT
Set persona {$persona}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 0);

        $this->assertEquals(12, $this->getUsageFrequency($persona->getAlias()));
    }

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenUseOfAliasFails()
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenPairAlreadyInUse()
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

    /**
     * @test
     * @group integration
     */
    public function returnsExpectedWarningWhenAliasAndAliasesArgumentsAreUsedTogether()
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

    /**
     * @test
     * @group integration
     */
    public function usesPairAndIncrementsUsageFrequencies()
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

        $localGubDotFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $expectedDisplay = <<<CONTENT
Set pair {$pair}.

CONTENT;

        $this->assertSame($expectedDisplay, $commandTester->getDisplay());
        $this->assertTrue($commandTester->getStatusCode() == 0);

        $this->assertEquals(12, $this->getUsageFrequency($john->getAlias()));
        $this->assertEquals(24, $this->getUsageFrequency($jane->getAlias()));
    }
}
