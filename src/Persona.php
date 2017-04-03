<?php
declare(strict_types=1);

namespace Stolt\GitUserBend;

use Stolt\GitUserBend\Exceptions\InvalidAlias;
use Stolt\GitUserBend\Exceptions\InvalidEmail;
use Stolt\GitUserBend\Exceptions\InvalidPersona;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Git\User;
use Stolt\GitUserBend\Traits\Guards;

class Persona implements \JsonSerializable
{
    use Guards;

    const MAX_ALIAS_LENGTH = 20;
    const REPOSITORY_USER_ALIAS = 'RU';

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var integer
     */
    private $usageFrequency;

    /**
     * @param string  $alias
     * @param string  $name
     * @param string  $email
     * @param integer $usageFrequency
     * @throws InvalidPersona
     */
    public function __construct(string $alias, string $name, string $email, int $usageFrequency = 0)
    {
        $this->alias = $alias;
        $this->name = $name;
        $this->email = $email;
        $this->usageFrequency = $usageFrequency;

        $this->guardValidity();
    }

    /**
     * @return void
     * @throws InvalidPersona
     */
    private function guardValidity()
    {
        try {
            $this->guardAlias($this->alias);
            $this->guardEmail($this->email);
        } catch (InvalidAlias $ia) {
            $exceptionMessage = "Persona alias is longer than "
                . self::MAX_ALIAS_LENGTH . " characters.";
            throw new InvalidPersona($exceptionMessage);
        } catch (InvalidEmail $ie) {
            $exceptionMessage = "Persona has an invalid email address '{$this->email}'.";
            throw new InvalidPersona($exceptionMessage);
        }
    }

    /**
     * @param  array  $persona A persona from a storage entry.
     * @return Persona
     */
    public static function fromStorageEntry(array $persona): Persona
    {
        return new Persona(
            $persona['alias'],
            $persona['name'],
            $persona['email'],
            $persona['usage_frequency']
        );
    }

    /**
     * @param  Stolt\GitUserBend\Git\Repository $repository
     * @return Stolt\GitUserBend\Persona
     */
    public static function fromRepository(Repository $repository): Persona
    {
        return $repository->getPersonaFromConfiguration();
    }

    /**
     * @return Stolt\GitUserBend\Git\User
     */
    public function factorUser(): User
    {
        return new User($this->name, $this->email, $this->alias);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return integer
     */
    public function getUsageFrequency(): int
    {
        return $this->usageFrequency;
    }

    /**
     * @param  Persona $persona The persona to compare against.
     * @return boolean
     */
    public function equals(Persona $persona): bool
    {
        return $this->__toString() === $persona->__toString();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        if ($this->getAlias() === self::REPOSITORY_USER_ALIAS
            || $this->hasPairName()
        ) {
            return $this->getName() . ' <' . $this->getEmail() . '>';
        }

        return $this->getAlias() . ' ~ ' . $this->getName()
            . ' <' . $this->getEmail() . '>';
    }

    /**
     * @return boolean
     */
    private function hasPairName(): bool
    {
        if (strstr($this->name, " and ") === false) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function gubFileSerialize(): array
    {
        return [
            'alias' => $this->getAlias(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'alias' => $this->getAlias(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'usage_frequency' => $this->getUsageFrequency(),
        ];
    }
}
