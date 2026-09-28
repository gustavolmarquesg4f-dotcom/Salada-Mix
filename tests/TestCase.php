<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Testes de views não dependem de assets compilados; o CI compila Vite separadamente.
        $this->withoutVite();
    }
}
