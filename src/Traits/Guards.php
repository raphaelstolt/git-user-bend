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
     * @return string
     * @throws Stolt\GitUserBend\Exceptions\InvalidAlias
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
     * @return array
     * @throws Stolt\GitUserBend\Exceptions\Exception
     * @throws Stolt\GitUserBend\Exceptions\NoDefinedPersonas
     * @throws Stolt\GitUserBend\Exceptions\UnknownPersona
     */
    private function guardAliases($aliases)
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
     * @param  string $alias
     * @return string
     * @throws Stolt\GitUserBend\Exceptions\Exception
     */
    public function guardRequiredAlias($alias)
    {
        if ($alias == null) {
            throw new Exception('Required alias argument not provided.');
        }

        return $alias;
    }

    /**
     * @param  string $email
     * @return void
     * @throws Stolt\GitUserBend\Exceptions\InvalidEmail
     */
    public function guardEmail(string $email)
    {
        $validEmail = filter_var($email, FILTER_VALIDATE_EMAIL);

        if ($validEmail === false) {
            $exceptionMessage = "The provided email address '{$email}' is invalid.";
            throw new InvalidEmail($exceptionMessage);
        }
    }
}
