<?php

namespace Stolt\GitUserBend\Tests\Traits;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stolt\GitUserBend\Exceptions\InvalidAlias;
use Stolt\GitUserBend\Exceptions\InvalidEmail;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Traits\Guards;

class GuardsTest extends TestCase
{
    use Guards;

    #[Test]
    #[Group('unit')]
    public function guardsAliasLength(): void
    {
        $maxAliasLength = Persona::MAX_ALIAS_LENGTH;
        $alias = str_repeat('a', $maxAliasLength + 1);

        $this->expectException(InvalidAlias::class);
        $expectedExceptionMessage = "The provided alias '{$alias}' is longer than "
            . "'{$maxAliasLength}' characters.";
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->guardAlias($alias);
    }

    #[Test]
    #[Group('unit')]
    public function guardsAliasNotEmpty(): void
    {
        $this->expectException(InvalidAlias::class);
        $this->expectExceptionMessage('The provided alias is empty.');

        $this->guardAlias(' ');
    }

    #[Test]
    #[Group('unit')]
    public function guardsEmailAddress(): void
    {
        $email = '1234';

        $this->expectException(InvalidEmail::class);
        $expectedExceptionMessage = "The provided email address '{$email}' is invalid.";
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->guardEmail($email);
    }
}
