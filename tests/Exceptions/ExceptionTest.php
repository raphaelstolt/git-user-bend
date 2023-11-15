<?php

namespace Stolt\GitUserBend\Tests\Exceptions;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stolt\GitUserBend\Exceptions\Exception;

class ExceptionTest extends TestCase
{

    #[Test]
    #[Group('unit')]
    public function returnInforizedMessage(): void
    {
        $exceptionMessage = "The persona 'foo' is already "
            . "present in '/tmp/.gub'.";
        $exception = new Exception($exceptionMessage);

        $expectedInforizedMessage = "The persona <info>foo</info> is already "
            . "present in <info>/tmp/.gub</info>.";

        $this->assertEquals($expectedInforizedMessage, $exception->getInforizedMessage());

        $exceptionMessage = "The directory '/tmp/.gub' "
            . "doesn't exist.";
        $exception = new Exception($exceptionMessage);

        $expectedInforizedMessage = "The directory <info>/tmp/.gub</info> "
            . "doesn't exist.";

        $this->assertEquals($expectedInforizedMessage, $exception->getInforizedMessage());
    }
}
