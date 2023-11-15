<?php
declare(strict_types=1);

namespace Stolt\GitUserBend\Traits;

use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Exceptions\InvalidAlias;
use Stolt\GitUserBend\Exceptions\InvalidEmail;
use Stolt\GitUserBend\Exceptions\NoDefinedPersonas;
use Stolt\GitUserBend\Exceptions\UnknownPersona;
use Stolt\GitUserBend\Persona;

trait Guards
{
    /**
     * @param  string $alias
     * @throws InvalidAlias
     * @return string
     */
    public function guardAlias(string $alias)
    {
        $maxAliasLength = Persona::MAX_ALIAS_LENGTH;
        if (strlen($alias) > $maxAliasLength) {
            $exceptionMessage = "The provided alias '{$alias}' is longer than "
                . "'{$maxAliasLength}' characters.";
            throw new InvalidAlias($exceptionMessage);
        }

        if (trim($alias) === '') {
            $exceptionMessage = "The provided alias is empty.";
            throw new InvalidAlias($exceptionMessage);
        }

        return $alias;
    }

    /**
     * @param  string $aliases
     * @throws Exception
     * @throws NoDefinedPersonas
     * @throws UnknownPersona
     * @return array
     */
    private function guardAliases(string $aliases): array
    {
        $aliases = explode(',', $aliases);
        if (count($aliases) === 1) {
            throw new Exception('Only provided a single persona alias.');
        }

        $personas = $this->storage->all();
        $pairPersonas = [];

        foreach ($aliases as $alias) {
            $pairPersonas[] = $personas->getByAlias(trim($alias));
        }

        return $pairPersonas;
    }

    /**
     * @param string|null $alias
     * @throws Exception
     * @return string
     */
    public function guardRequiredAlias(?string $alias): string
    {
        if ($alias == null) {
            throw new Exception('Required alias argument not provided.');
        }

        return $alias;
    }

    /**
     * @param  string $email
     * @throws InvalidEmail
     * @return void
     */
    public function guardEmail(string $email): void
    {
        $validEmail = filter_var($email, FILTER_VALIDATE_EMAIL);

        if ($validEmail === false) {
            $exceptionMessage = "The provided email address '{$email}' is invalid.";
            throw new InvalidEmail($exceptionMessage);
        }
    }
}
