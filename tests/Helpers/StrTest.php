<?php

namespace Stolt\GitUserBend\Tests\Helpers;

use Stolt\GitUserBend\Helpers\Str as OsHelper;
use Stolt\GitUserBend\Tests\TestCase;

class StrTest extends TestCase
{
    /**
     * @test
     * @group unit
     */
    public function canDetermineIfWindowsOrNot()
    {
        $osHelper = new OsHelper();
        if ($osHelper->isWindows()) {
            $this->assertTrue($osHelper->isWindows());
        } else {
            $this->assertFalse($osHelper->isWindows());
        }

        $this->assertTrue($osHelper->isWindows('WIn'));
        $this->assertFalse($osHelper->isWindows('Darwin'));
    }
}
