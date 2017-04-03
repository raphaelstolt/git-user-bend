<?php
declare(strict_types=1);

namespace Stolt\GitUserBend\Git;

use Stolt\GitUserBend\Persona;

class User
{
    const REPOSITORY_USER_ALIAS = 'RU';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $alias;

    /**
     * @param string $name
     * @param string $email
     * @param string $alias
     */
    public function __construct(
        $name = null,
        $email = null,
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
     * @return string
     */
    public function getName(): string
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
     * @return string
     */
    public function getEmail(): string
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
     * @return Stolt\GitUserBend\Persona
     * @throws Stolt\GitUserBend\Exceptions\InvalidPersona
     */
    public function factorPersona(): Persona
    {
        return new Persona(
            $this->getAlias(),
            $this->getName(),
            $this->getEmail()
        );
    }
}
