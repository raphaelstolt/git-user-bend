<?php
declare(strict_types=1);

namespace Stolt\GitUserBend\Persona;

use \ArrayIterator;
use \Countable;
use \IteratorAggregate;
use \JsonSerializable;
use Stolt\GitUserBend\Exceptions\AlreadyAliasedPersona;
use Stolt\GitUserBend\Exceptions\DuplicateAlias;
use Stolt\GitUserBend\Exceptions\NoDefinedPersonas;
use Stolt\GitUserBend\Exceptions\UnknownPersona;
use Stolt\GitUserBend\Persona;

class Collection implements Countable, IteratorAggregate, JsonSerializable
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
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->personas);
    }

    /**
     * @return integer
     */
    public function count(): int
    {
        return count($this->personas);
    }

    /**
     * @param  string $alias The alias of the persona to remove.
     * @return void
     * @throws NoDefinedPersonas
     * @throws UnknownPersona
     */
    public function removeByAlias(string $alias)
    {
        $personas = $this->getByAlias($alias);

        foreach ($this->personas as $index => $persona) {
            if ($persona->getAlias() === $alias) {
                unset($this->personas[$index]);
            }
        }
    }

    /**
     * @param  string $alias The alias to look up.
     * @return Persona
     * @throws NoDefinedPersonas
     * @throws UnknownPersona
     */
    public function getByAlias(string $alias): Persona
    {
        if ($this->count() === 0) {
            throw new NoDefinedPersonas('There are no defined personas.');
        }

        foreach ($this->personas as $persona) {
            if ($persona->getAlias() === $alias) {
                return $persona;
            }
        }
        $exceptionMessage = "No known persona for alias '{$alias}'.";
        throw new UnknownPersona($exceptionMessage);
    }

    /**
     * @param  string $name The name to look up.
     * @param  string $email The email to look up.
     * @return Persona
     * @throws NoDefinedPersonas
     * @throws UnknownPersona
     */
    public function getByNameAndEmail(string $name, string $email): Persona
    {
        if ($this->count() === 0) {
            throw new NoDefinedPersonas('There are no defined personas.');
        }

        foreach ($this->personas as $persona) {
            if ($persona->getName() === $name && $persona->getEmail() === $email) {
                return $persona;
            }
        }
        $exceptionMessage = "No known persona for name '{$name}' and email '{$email}'.";
        throw new UnknownPersona($exceptionMessage);
    }

    /**
     * @param  string $name
     * @param  Persona $lookupPersona
     * @return boolean
     */
    public function hasAliasedPersona(Persona $lookupPersona): bool
    {
        if ($this->count() === 0) {
            return false;
        }

        foreach ($this->personas as $persona) {
            if ($persona->getName() === $lookupPersona->getName()
                && $persona->getEmail() === $lookupPersona->getEmail()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->personas;
    }

    /**
     * Populates and returns a pair of personas.
     *
     * @param  array $aliases The aliases of the pair to be.
     * @return Pair
     * @throws NoDefinedPersonas
     * @throws UnknownPersona
     */
    public function pair(array $aliases): Pair
    {
        $pair = new Pair();
        foreach ($aliases as $alias) {
            $pair->add($this->getByAlias($alias));
        }

        return $pair;
    }

    /**
     * Returns a collection with all personas sorted by
     * their usage frequency.
     *
     * @return Collection
     */
    public function sorted(): Collection
    {
        $personas = $this->personas;

        usort($personas, function ($a, $b) {
            return (int) $a->getUsageFrequency() < (int) $b->getUsageFrequency() ? +1 : -1;
        });

        $sortedCollection = new Collection();
        foreach ($personas as $persona) {
            $sortedCollection->add($persona);
        }

        return $sortedCollection;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->sorted()->all();
    }
}
