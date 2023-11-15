<?php

namespace Stolt\GitUserBend\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Helpers\Str as OsHelper;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Storage;

class TestCase extends PHPUnitTestCase
{
    /**
     * @var string
     */
    protected $temporaryDirectory;

    /**
     * Set up temporary directory.
     *
     * @return void
     */
    protected function setUpTemporaryDirectory()
    {
        if ((new OsHelper())->isWindows() === false) {
            ini_set('sys_temp_dir', '/tmp/gub');
            $this->temporaryDirectory = '/tmp/gub';
        } else {
            $this->temporaryDirectory = sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . 'gub';
        }

        if (!file_exists($this->temporaryDirectory)) {
            mkdir($this->temporaryDirectory);
        }
    }

    /**
     * Create a temporary Git repository.
     *
     * @param User|null $user If set a local Git user is configured.
     * @return void
     */
    protected function createTemporaryGitRepository(User $user = null): void
    {
        $currentDirectory = getcwd();
        chdir($this->temporaryDirectory);
        exec('git init');

        if ($user) {
            if ($user->hasName()) {
                exec("git config --local user.name \"{$user->getName()}\"");
            }

            if ($user->hasEmail()) {
                exec("git config --local user.email \"{$user->getEmail()}\"");
            }

            if ($user->hasAlias()) {
                exec("git config --local user.alias \"{$user->getAlias()}\"");
            }
        }

        chdir((string) $currentDirectory);
    }

    /**
     * Remove directory and files in it.
     *
     * @param string $directory
     */
    protected function removeDirectory(string $directory): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        /** @var \SplFileInfo $fileinfo */
        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                @rmdir($fileinfo->getRealPath());
                continue;
            }
            @unlink($fileinfo->getRealPath());
        }

        @rmdir($directory);
    }

    /**
     * Create temporary storage file.
     *
     * @param  string $content Content of file.
     *
     * @return integer
     */
    protected function createTemporaryStorageFile(string $content): int
    {
        $temporaryStorageFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Storage::FILE_NAME;

        return (int) file_put_contents($temporaryStorageFile, $content);
    }

    /**
     * Returns the usage frequency by a persona alias.
     *
     * @param  string $alias
     * @return integer
     */
    protected function getUsageFrequency($alias): int
    {
        $temporaryStorageFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Storage::FILE_NAME;

        $storage = new Storage($temporaryStorageFile);
        $personas = $storage->all();
        $personaByAlias = $personas->getByAlias($alias);

        return $personaByAlias->getUsageFrequency();
    }


    /**
     * Create temporary gub dotfile.
     *
     * @param Persona $persona The persona to set in the gub dotfile.
     *
     * @return integer
     */
    protected function createTemporaryGubDotfile(Persona $persona): int
    {
        $temporaryGubDotfile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . Repository::GUB_FILENAME;

        return (int) file_put_contents(
            $temporaryGubDotfile,
            json_encode($persona->gubFileSerialize())
        );
    }

    /**
     * An invalid alias data provider.
     *
     * @return array
     */
    public static function invalidAliases(): array
    {
        $maxAliasLength = Persona::MAX_ALIAS_LENGTH;
        $tooLongAlias = str_repeat("a", Persona::MAX_ALIAS_LENGTH + 1);

        return [
            'empty_alias' => ['   ', 'The provided alias is empty'],
            'too_long_alias' => [$tooLongAlias, "The provided alias {$tooLongAlias} is longer than "
                . "{$maxAliasLength} characters"],
        ];
    }
}
