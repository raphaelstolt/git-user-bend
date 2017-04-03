<?php

namespace Stolt\GitUserBend\Tests\Git;

use \phpmock\phpunit\PHPMock;
use Stolt\GitUserBend\Exceptions\InvalidGubDotFile;
use Stolt\GitUserBend\Exceptions\NonExistentGubDotFile;
use Stolt\GitUserBend\Exceptions\NotADirectory;
use Stolt\GitUserBend\Exceptions\NotAGitRepository;
use Stolt\GitUserBend\Exceptions\UnresolvablePair;
use Stolt\GitUserBend\Exceptions\UnresolvablePersona;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Pair;
use Stolt\GitUserBend\Tests\TestCase;

class RepositoryTest extends TestCase
{
    use PHPMock;

    /**
     * Set up test environment.
     */
    protected function setUp()
    {
        $this->setUpTemporaryDirectory();
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
     * @group unit
     */
    public function throwsExpectedExceptionWhenNotARepository()
    {
        $this->expectException(NotAGitRepository::class);
        $expectedExceptionMessage = "No Git repository in '{$this->temporaryDirectory}'.";
        $this->expectExceptionMessage($expectedExceptionMessage);

        $repository = new Repository($this->temporaryDirectory);
        $repository->setDirectory($this->temporaryDirectory);
    }

    /**
     * @test
     * @group unit
     */
    public function returnsGubDotFilePath()
    {
        $expectedGubDotFilePath = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $repository = new Repository($this->temporaryDirectory);

        $this->assertEquals(
            $expectedGubDotFilePath,
            $repository->getGubDotFilePath()
        );
    }

    /**
     * @test
     * @group unit
     */
    public function setsLocalRepositoryUser()
    {
        $localRepositoryUser = new User('John Doe', 'john.doe@example.org');

        $this->createTemporaryGitRepository();

        $repository = new Repository($this->temporaryDirectory);
        $setAUser = $repository->setUser($localRepositoryUser);

        $this->assertTrue($setAUser);
        $this->assertEquals(
            $localRepositoryUser->factorPersona(),
            $repository->getPersonaFromConfiguration()
        );
    }

    /**
     * @test
     * @group unit
     * @runInSeparateProcess
     */
    public function returnsPersonaFromGlobalGitConfiguration()
    {
        $expectedRepositoryUser = new User('Jane Doe', 'jane.doe@example.org');

        $this->createTemporaryGitRepository();

        $mockedOutput = [
            'user.name Jane Doe',
            'user.email jane.doe@example.org',
        ];

        $exec = $this->getFunctionMock('Stolt\GitUserBend\Git', 'exec');
        $exec->expects($this->at(0))->willReturnCallback(
            function ($command, &$output, &$returnValue) {
                $returnValue = 1;
            }
        );
        $exec->expects($this->at(1))->willReturnCallback(
            function ($command, &$output, &$returnValue) use ($mockedOutput) {
                $output = $mockedOutput;
                $returnValue = 0;
            }
        );

        $repository = new Repository($this->temporaryDirectory);

        $this->assertEquals(
            $expectedRepositoryUser->factorPersona(),
            $repository->getPersonaFromConfiguration()
        );
    }

    /**
     * @test
     * @group unit
     * @runInSeparateProcess
     */
    public function returnsPersonaFromGlobalGitConfigurationOnWindows()
    {
        $expectedRepositoryUser = new User('Jane Doe', 'jane.doe@example.org');

        $this->createTemporaryGitRepository();

        $mockedOutput = [
            "user.name 'Jane Doe'",
            "user.email 'jane.doe@example.org'",
        ];

        $exec = $this->getFunctionMock('Stolt\GitUserBend\Git', 'exec');
        $exec->expects($this->at(0))->willReturnCallback(
            function ($command, &$output, &$returnValue) {
                $returnValue = 1;
            }
        );
        $exec->expects($this->at(1))->willReturnCallback(
            function ($command, &$output, &$returnValue) use ($mockedOutput) {
                $output = $mockedOutput;
                $returnValue = 0;
            }
        );

        $repository = new Repository($this->temporaryDirectory);

        $this->assertEquals(
            $expectedRepositoryUser->factorPersona(),
            $repository->getPersonaFromConfiguration()
        );
    }

    /**
     * @test
     * @group unit
     */
    public function returnsPersonaFromLocalGitConfiguration()
    {
        $localRepositoryUser = new User('John Doe', 'john.doe@example.org', 'jd');
        $expectedPersona = new Persona(
            $localRepositoryUser->getAlias(),
            $localRepositoryUser->getName(),
            $localRepositoryUser->getEmail()
        );

        $this->createTemporaryGitRepository($localRepositoryUser);

        $repository = new Repository($this->temporaryDirectory);

        $this->assertEquals(
            $expectedPersona,
            $repository->getPersonaFromConfiguration()
        );
    }

    /**
     * @test
     * @group unit
     * @runInSeparateProcess
     */
    public function returnsPersonaEmailFromGlobalAndPersonaNameFromLocalGitConfiguration()
    {
        $expectedPersona = new Persona(
            Persona::REPOSITORY_USER_ALIAS,
            'Jane Doe',
            'jane.doe@example.org'
        );

        $this->createTemporaryGitRepository(
            new User($expectedPersona->getName())
        );

        $localMockedOutput = [
            'user.name Jane Doe',
        ];

        $exec = $this->getFunctionMock('Stolt\GitUserBend\Git', 'exec');
        $exec->expects($this->at(0))->willReturnCallback(
            function ($command, &$output, &$returnValue) use ($localMockedOutput) {
                $output = $localMockedOutput;
                $returnValue = 0;
            }
        );

        $globalMockedOutput = [
            'user.email jane.doe@example.org',
        ];

        $exec->expects($this->at(1))->willReturnCallback(
            function ($command, &$output, &$returnValue) use ($globalMockedOutput) {
                $output = $globalMockedOutput;
                $returnValue = 0;
            }
        );

        $repository = new Repository($this->temporaryDirectory);

        $this->assertEquals(
            $expectedPersona,
            $repository->getPersonaFromConfiguration()
        );
    }

    /**
     * @test
     * @group unit
     * @runInSeparateProcess
     */
    public function returnsPersonaNameFromGlobalAndPersonaEmailFromLocalGitConfiguration()
    {
        $expectedPersona = new Persona(
            Persona::REPOSITORY_USER_ALIAS,
            'Jane Doe',
            'jane.doe@example.org'
        );

        $this->createTemporaryGitRepository(
            new User($expectedPersona->getName())
        );

        $localMockedOutput = [
            'user.email jane.doe@example.org',
        ];

        $exec = $this->getFunctionMock('Stolt\GitUserBend\Git', 'exec');
        $exec->expects($this->at(0))->willReturnCallback(
            function ($command, &$output, &$returnValue) use ($localMockedOutput) {
                $output = $localMockedOutput;
                $returnValue = 0;
            }
        );

        $globalMockedOutput = [
            'user.name Jane Doe',
        ];

        $exec->expects($this->at(1))->willReturnCallback(
            function ($command, &$output, &$returnValue) use ($globalMockedOutput) {
                $output = $globalMockedOutput;
                $returnValue = 0;
            }
        );

        $repository = new Repository($this->temporaryDirectory);

        $this->assertEquals(
            $expectedPersona,
            $repository->getPersonaFromConfiguration()
        );
    }

    /**
     * @test
     * @group unit
     */
    public function returnsPersonaFromLocalGubDotFile()
    {
        $this->createTemporaryGitRepository();

        $gubFilePersona = new Persona('jd', 'John Doe', 'john.doe@example.org');
        $this->createTemporaryGubDotFile($gubFilePersona);

        $repository = new Repository($this->temporaryDirectory);

        $this->assertEquals(
            $gubFilePersona,
            $repository->getPersonaFromGubDotFile()
        );
    }

    /**
     * @test
     * @group unit
     */
    public function createsLocalGubDotFileFromPersona()
    {
        $this->createTemporaryGitRepository();

        $persona = new Persona('jd', 'John Doe', 'john.doe@example.org');
        $repository = new Repository($this->temporaryDirectory);

        $this->assertTrue($repository->createGubDotFile($persona));
        $this->assertEquals(
            $persona,
            $repository->getPersonaFromGubDotFile()
        );

        $expectedGubDotFileContent = json_encode(
            $persona->gubFileSerialize(),
            JSON_PRETTY_PRINT
        );
        $expectedGubDotFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        $this->assertStringEqualsFile(
            $expectedGubDotFile,
            $expectedGubDotFileContent . "\n"
        );
    }

    /**
     * @test
     * @group unit
     */
    public function detectsPair()
    {
        $pair = new Pair();
        $pair->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $pair->add(new Persona('ja', 'Jane Doe', 'jane.doe@example.org'));

        $this->createTemporaryGitRepository($pair->factorUser());

        $repository = new Repository($this->temporaryDirectory);

        $this->assertTrue($repository->hasPair());
    }

    /**
     * @test
     * @group unit
     */
    public function throwsExpectedExceptionWhenPairUserNotResolvableFromGitConfiguration()
    {
        $this->expectException(UnresolvablePair::class);
        $expectedExceptionMessage = 'Unable to resolve pair from Git configuration.';
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->createTemporaryGitRepository();

        $repository = new Repository($this->temporaryDirectory);
        $repository->getPairUserFromConfiguration();
    }

    /**
     * @test
     * @group unit
     */
    public function returnsExpectedPairUserFromGitConfiguration()
    {
        $pair = new Pair();
        $pair->add(new Persona('jd', 'John Doe', 'john.doe@example.org'));
        $pair->add(new Persona('ja', 'Jane Doe', 'jane.doe@example.org'));

        $this->createTemporaryGitRepository($pair->factorUser());

        $repository = new Repository($this->temporaryDirectory);

        $this->assertEquals(
            $pair->factorUser(),
            $repository->getPairUserFromConfiguration()
        );
    }

    /**
     * @test
     * @group unit
     */
    public function throwsExpectedExceptionForNonExistentGubDotFile()
    {
        $this->expectException(NonExistentGubDotFile::class);
        $expectedExceptionMessage = "No .gub file present in '{$this->temporaryDirectory}'.";
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->createTemporaryGitRepository();

        $repository = new Repository($this->temporaryDirectory);
        $repository->getPersonaFromGubDotFile();
    }

    /**
     * @test
     * @group unit
     */
    public function throwsExpectedExceptionForInvalidGubDotFile()
    {
        $this->expectException(InvalidGubDotFile::class);
        $expectedExceptionMessage = 'Invalid .gub file content. JSON error: Syntax error.';
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->createTemporaryGitRepository();

        $temporaryGubFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;
        file_put_contents($temporaryGubFile, 'fooo');

        $repository = new Repository($this->temporaryDirectory);
        $repository->getPersonaFromGubDotFile();
    }

    /**
     * @test
     * @group unit
     */
    public function throwsExpectedExceptionForInvalidGubDotFileContent()
    {
        $this->expectException(InvalidGubDotFile::class);
        $expectedExceptionMessage = 'Invalid .gub file content '
            . 'unable to create a persona from it.';

        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->createTemporaryGitRepository();

        $temporaryGubFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;
        file_put_contents($temporaryGubFile, '{"ALIAS":"jd","NAME":"John Doe"}');

        $repository = new Repository($this->temporaryDirectory);
        $repository->getPersonaFromGubDotFile();
    }

    /**
     * @test
     * @group unit
     * @runInSeparateProcess
     */
    public function throwsExpectedExceptionWhenPersonaNotResolvableFromGitConfiguration()
    {
        $this->expectException(UnresolvablePersona::class);
        $this->expectExceptionMessage('Unable to resolve persona from Git configuration.');

        $this->createTemporaryGitRepository();

        $exec = $this->getFunctionMock('Stolt\GitUserBend\Git', 'exec');
        $exec->expects($this->any())->willReturn(1);

        (new Repository($this->temporaryDirectory))->getPersonaFromConfiguration();
    }

    /**
     * @test
     * @group unit
     */
    public function throwsExpectedExceptionWhenDirectoryToSetIsNotADirectory()
    {
        $this->expectException(NotADirectory::class);
        $expectedExceptionMessage = "The directory '/out/of/orbit' doesn't exist.";
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->createTemporaryGitRepository();

        (new Repository($this->temporaryDirectory))->setDirectory('/out/of/orbit');
    }

    /**
     * @test
     * @group unit
     */
    public function throwsExpectedExceptionWhenDirectoryToSetIsNotAGitRepository()
    {
        $this->expectException(NotAGitRepository::class);
        $expectedExceptionMessage = "No Git repository in '{$this->temporaryDirectory}'.";
        $this->expectExceptionMessage($expectedExceptionMessage);

        (new Repository($this->temporaryDirectory))->setDirectory($this->temporaryDirectory);
    }
}
