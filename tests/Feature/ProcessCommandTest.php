<?php

namespace vicgonvt\LaraPress\Tests;

use Symfony\Component\Console\Exception\CommandNotFoundException;

class ProcessCommandTest extends TestCase
{
    /** @test */
    public function command_is_available()
    {
        try {
            $this->artisan('larapress:process');
            $this->assertTrue(true);
        } catch (CommandNotFoundException $e) {
            $this->fail('Unable to locate the command \'larapress:process\'');
        }
    }
}
