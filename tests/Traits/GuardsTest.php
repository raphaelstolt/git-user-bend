<?php

namespace Stolt\GitUserBend\Tests\Traits;

use PHPUnit\Framework\TestCase;
use Stolt\GitUserBend\Traits\Guards;
use Stolt\GitUserBend\Exceptions\InvalidAlias;
use Stolt\GitUserBend\Exceptions\InvalidEmail;
use Stolt\GitUserBend\Persona;

class GuardsTest extends TestCase
{
    use Guards;

    /**
     * @test
     * @group unit
     */
    public function guardsAliasLength()
    {
        $maxAliasLength = Persona::MAX_ALIAS_LENGTH;
        $alias = str_repeat('a', $maxAliasLength + 1);

        $this->expectException(InvalidAlias::class);
        $expectedExceptionMessage = "The provided alias '{$alias}' is longer than "
            . "'{$maxAliasLength}' characters.";
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->guardAlias($alias);
    }

    /**
     * @test
     * @group unit
     */
    public function guardsAliasNotEmpty()
    {
        $this->expectException(InvalidAlias::class);
        $this->expectExceptionMessage('The provided alias is empty.');

        $this->guardAlias(' ');
    }

    /**
     * @test
     * @group unit
     */
    public function guardsEmailAddress()
    {
        $email = 1234;

        $this->expectException(InvalidEmail::class);
        $expectedExceptionMessage = "The provided email address '{$email}' is invalid.";
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->guardEmail($email);
    }
}
