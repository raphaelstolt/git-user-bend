<?php
declare(strict_types=1);

namespace Stolt\GitUserBend\Persona;

use \ArrayIterator;
use \Countable;
use \IteratorAggregate;
use \RuntimeException;
use Stolt\GitUserBend\Exceptions\AlreadyAliasedPersona;
use Stolt\GitUserBend\Exceptions\DuplicateAlias;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Persona;

class Pair implements Countable, IteratorAggregate
{
    /**
     * @var array
     */
    private $personas = [];

    /**
     * @param  Persona $persona
     * @throws DuplicateAlias
     * @throws AlreadyAliasedPersona
     */
    public function add(Persona $persona)
    {
        foreach ($this->personas as $presentPersona) {
            if ($presentPersona->getAlias() === $persona->getAlias()) {
                $exceptionMessage = "The alias '{$persona->getAlias()}' "
                    . "is already present.";
                throw new DuplicateAlias($exceptionMessage);
            }
            if ($presentPersona->getName() === $persona->getName()
                && $presentPersona->getEmail() === $persona->getEmail()
            ) {
                $exceptionMessage = "The persona is already aliased"
                    . " to '{$presentPersona->getAlias()}'.";
                throw new AlreadyAliasedPersona($exceptionMessage);
            }
        }
        $this->personas[] = $persona;
    }

    /**
     * @return integer
     */
    public function count(): int
    {
        return count($this->personas);
    }

    /**
     * @throws \RuntimeException
     * @return Stolt\GitUserBend\Git\User
     */
    public function factorUser(): User
    {
        if ($this->count() === 0) {
            throw new RuntimeException('No personas to factor user from.');
        }

        if ($this->count() === 1) {
            throw new RuntimeException('Not enough personas to factor user from.');
        }

        $personas = $this->personas;
        $email = $personas[0]->getEmail();

        if ($this->count() === 2) {
            $names = "{$personas[0]->getName()} "
                . "and {$personas[1]->getName()}";

            return new User($names, $email, $personas[0]->getAlias());
        }

        $lastPersona = array_pop($personas);

        $names = [];
        foreach ($personas as $persona) {
            $names[] = $persona->getName();
        }

        $names = implode(', ', $names)
            . ", and {$lastPersona->getName()}";

        return new User($names, $email, $personas[0]->getAlias());
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->personas);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $user = $this->factorUser();

        return $user->getName()
            . ' <' . $user->getEmail() . '>';
    }
}
