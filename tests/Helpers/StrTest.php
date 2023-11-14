<?php

namespace Stolt\GitUserBend\Tests\Helpers;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stolt\GitUserBend\Helpers\Str as OsHelper;
use Stolt\GitUserBend\Tests\TestCase;

class StrTest extends TestCase
{
    #[Test]
    #[Group('unit')]
    public function canDetermineIfWindowsOrNot(): void
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
