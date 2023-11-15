<?php

namespace Stolt\GitUserBend\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    #[Test]
    #[Group('integration')]
    public function executableIsAvailable(): void
    {
        $binaryCommand = 'php bin/git-user-bend';

        exec($binaryCommand, $output, $returnValue);

        $this->assertStringStartsWith(
            'Git user bend',
            $output[1],
            'Expected application name not present.'
        );
        $this->assertEquals(0, $returnValue);
    }
}
