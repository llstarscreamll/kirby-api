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
     * @param  string  $path
     * @return $this
     */
    public function assertJsonHasPath(string $path)
    {
        PHPUnit::assertTrue(Arr::has($this->decodeResponseJson(), $path));

        return $this;
    }

    /**
     * @param  string  $path
     * @return $this
     */
    public function assertJsonMissingPath(string $path)
    {
        PHPUnit::assertFalse(Arr::has($this->decodeResponseJson(), $path));

        return $this;
    }
}
