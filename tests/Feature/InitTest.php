<?php

namespace vicgonvt\LaraPress\Tests;

class InitTest extends TestCase
{
    /** @test */
    public function test_inits()
    {
        $this->artisan('migrate', ['--database' => 'testdb'])->run();

        $this->assertTrue(true);
    }
}