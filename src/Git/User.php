<?php
declare(strict_types=1);

namespace Stolt\GitUserBend\Git;

use Stolt\GitUserBend\Exceptions\InvalidPersona;
use Stolt\GitUserBend\Persona;

class User
{
    const REPOSITORY_USER_ALIAS = 'RU';

    /**
     * @var string
     */
    private string|null $name;

    /**
     * @var string
     */
    private string|null $email;

    /**
     * @var string
     */
    private $alias;

    /**
     * @param string|null $name
     * @param string|null $email
     * @param string $alias
     */
    public function __construct(
        ?string $name = null,
        ?string $email = null,
        string $alias = User::REPOSITORY_USER_ALIAS
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->alias = $alias;
    }

    /**
     * @return boolean
     */
    public function hasName(): bool
    {
        return $this->name !== null;
    }

    /**
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * @param  string $name
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getEmail(): string|null
    {
        return $this->email;
    }

    /**
     * @param  string $email
     * @return void
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return boolean
     */
    public function hasEmail(): bool
    {
        return $this->email !== null;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return boolean
     */
    public function hasAlias(): bool
    {
        return $this->alias !== null;
    }

    /**
     * @return boolean
     */
    public function partial(): bool
    {
        if ($this->hasName() && $this->hasEmail()) {
            return false;
        }

        return true;
    }

    /**
     * @throws InvalidPersona
     * @return Persona
     */
    public function factorPersona(): Persona
    {
        return new Persona(
            $this->getAlias(),
            (string) $this->getName(),
            (string) $this->getEmail()
        );
    }
}
