<?php

namespace Stolt\GitUserBend\Tests\Exceptions;

use Stolt\GitUserBend\Exceptions\Exception;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    /**
     * @test
     * @group unit
     */
    public function returnInforizedMessage()
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
