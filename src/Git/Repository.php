<?php
declare(strict_types=1);

namespace Stolt\GitUserBend\Git;

use Stolt\GitUserBend\Exceptions\InvalidGubDotfile;
use Stolt\GitUserBend\Exceptions\NonExistentGubDotfile;
use Stolt\GitUserBend\Exceptions\NotADirectory;
use Stolt\GitUserBend\Exceptions\NotAGitRepository;
use Stolt\GitUserBend\Exceptions\UnresolvablePair;
use Stolt\GitUserBend\Exceptions\UnresolvablePersona;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Persona;

class Repository
{
    const GUB_FILENAME = '.gub';

    /**
     * @var Stolt\GitUserBend\Git\User
     */
    private $user;

    /**
     * @var string
     */
    private $directory;

    /**
     * @param  string $directory
     */
    public function __construct(string $directory = __DIR__)
    {
        $this->directory = $directory;
    }

    /**
     * @param  string $directory
     * @throws Stolt\GitUserBend\Exceptions\NotADirectory
     * @throws Stolt\GitUserBend\Exceptions\NotAGitRepository
     */
    public function setDirectory(string $directory)
    {
        if (!is_dir($directory)) {
            $message = "The directory '{$directory}' doesn't exist.";
            throw new NotADirectory($message);
        }

        $expectedGitDirectory = $directory . DIRECTORY_SEPARATOR . '.git';

        if (!is_dir($expectedGitDirectory)) {
            $exceptionMessage = "No Git repository in '{$directory}'.";
            throw new NotAGitRepository($exceptionMessage);
        }

        $this->directory = $directory;
    }

    /**
     * @return boolean
     */
    public function hasGubDotfile(): bool
    {
        $gubDotfile = $this->directory
            . DIRECTORY_SEPARATOR
            . self::GUB_FILENAME;

        return file_exists($gubDotfile);
    }

    /**
     * @return string
     */
    public function getGubDotfilePath(): string
    {
        return $this->directory
            . DIRECTORY_SEPARATOR
            . self::GUB_FILENAME;
    }

    /**
     * Creates a local .gub file.
     *
     * @param  Stolt\GitUserBend\Persona $persona
     * @return boolean
     */
    public function createGubDotfile(Persona $persona): bool
    {
        $gubDotfile = $this->directory
            . DIRECTORY_SEPARATOR
            . self::GUB_FILENAME;

        $gubDotfileContent = json_encode(
            $persona->gubFileSerialize(),
            JSON_PRETTY_PRINT
        );

        return file_put_contents(
            $gubDotfile,
            $gubDotfileContent . "\n"
        ) > 0;
    }

    /**
     * @return Stolt\GitUserBend\Persona
     * @throws InvalidGubDotfile
     * @throws NonExistentGubDotfile
     */
    public function getPersonaFromGubDotfile()
    {
        if (!$this->hasGubDotfile()) {
            $exceptionMessage = 'No ' . self::GUB_FILENAME . ' file present '
                . "in '{$this->directory}'.";
            throw new NonExistentGubDotfile($exceptionMessage);
        }

        $gubDotfile = $this->directory
            . DIRECTORY_SEPARATOR
            . self::GUB_FILENAME;

        $personaFromGubDotfile = json_decode(
            file_get_contents($gubDotfile),
            true
        );

        if ($personaFromGubDotfile === null) {
            $exceptionMessage = 'Invalid ' . self::GUB_FILENAME . ' file content. '
                . 'JSON error: ' . json_last_error_msg() . '.';
            throw new InvalidGubDotfile($exceptionMessage);
        }

        if (isset($personaFromGubDotfile['alias'])
            && isset($personaFromGubDotfile['name'])
            && isset($personaFromGubDotfile['email'])
        ) {
            return new Persona(
                $personaFromGubDotfile['alias'],
                $personaFromGubDotfile['name'],
                $personaFromGubDotfile['email']
            );
        }

        $exceptionMessage = 'Invalid ' . self::GUB_FILENAME . ' file content '
            . 'unable to create a persona from it.';
        throw new InvalidGubDotfile($exceptionMessage);
    }

    /**
     * @return Stolt\GitUserBend\Persona
     * @throws UnresolvablePersona
     */
    public function getPersonaFromConfiguration(): Persona
    {
        chdir($this->directory);
        $command = 'git config --get-regexp "^user.*"';
        exec($command, $output, $returnValue);

        if ($returnValue === 0) {
            $localGitUser = $this->factorUser($output);
            if ($localGitUser->partial() === false) {
                return $localGitUser->factorPersona();
            }
        }

        throw new UnresolvablePersona('Unable to resolve persona from Git configuration.');
    }

    /**
     * @return boolean
     */
    public function hasPair(): bool
    {
        chdir($this->directory);
        $command = 'git config --local --get-regexp "^user.*"';
        exec($command, $output, $returnValue);

        if ($returnValue === 0 && count($output) > 1) {
            $possiblePair = $this->factorUser($output);
            if (strstr($possiblePair->getName(), " and ")) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Stolt\GitUserBend\Git\User
     * @throws UnresolvablePair
     */
    public function getPairUserFromConfiguration(): User
    {
        if (!$this->hasPair()) {
            throw new UnresolvablePair('Unable to resolve pair from Git configuration.');
        }

        chdir($this->directory);
        $command = 'git config --local --get-regexp "^user.*"';
        exec($command, $output, $returnValue);

        return $this->factorUser($output);
    }

    /**
     * @param  User $user The use to configure locally.
     * @return boolean
     */
    public function setUser(User $user): bool
    {
        chdir($this->directory);

        $commands = [
            "git config --local user.email \"{$user->getEmail()}\"",
            "git config --local user.name \"{$user->getName()}\"",
        ];

        foreach ($commands as $command) {
            exec($command, $output, $returnValue);
            if ($returnValue !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array  $output
     * @return Stolt\GitUserBend\Git\User
     */
    private function factorUser(array $output): User
    {
        foreach ($output as $keyValueLine) {
            list($key, $value) = explode(' ', $keyValueLine, 2);
            $key = str_replace('user.', '', $key);
            $user[$key] = str_replace("'", '', $value);
        }

        $name = isset($user['name']) ? $user['name'] : null;
        $email = isset($user['email']) ? $user['email'] : null;
        $alias = isset($user['alias']) ? $user['alias'] : User::REPOSITORY_USER_ALIAS;

        return new User($name, $email, $alias);
    }
}
