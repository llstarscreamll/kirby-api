<?php

namespace Tests;

trait CreateTestResponse
{
    /**
     * Create the test response instance from the given response.
     *
     * @param \Illuminate\Http\Response $response
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function createTestResponse($response)
    {
        return TestResponse::fromBaseResponse($response);
    }
}
