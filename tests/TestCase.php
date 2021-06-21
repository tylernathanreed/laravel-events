<?php

namespace Reedware\LaravelEvents\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase as TestBase;

abstract class TestCase extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        m::close();
    }
}