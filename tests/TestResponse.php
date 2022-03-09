<?php

namespace Tests;

use Illuminate\Support\Arr;
use Illuminate\Testing\Assert as PHPUnit;
use Illuminate\Testing\TestResponse as BaseTestResponse;

/**
 * Class TestResponse.
 *
 * @mixing \Illuminate\Foundation\Testing\TestResponse
 */
class TestResponse extends BaseTestResponse
{
    /**
     * @return $this
     */
    public function assertJsonHasPath(string $path)
    {
        PHPUnit::assertTrue(Arr::has($this->decodeResponseJson(), $path));

        return $this;
    }

    /**
     * @return $this
     */
    public function assertJsonMissingPath(string $path)
    {
        PHPUnit::assertFalse(Arr::has($this->decodeResponseJson(), $path));

        return $this;
    }
}
