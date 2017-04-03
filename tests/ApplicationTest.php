<?php

namespace Stolt\GitUserBend\Tests;

use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    /**
     * @test
     * @group integration
     */
    public function executableIsAvailable()
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
