<?php

namespace Authentication;

use Kirby\Users\Models\User;

/**
 * Class GetUserTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetUserTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/auth/user';

    /**
     * @test
     */
    public function whenBearerTokenIsValidExpectAcceptedWithMessage()
    {
        $this->actingAs(factory(User::class)->create(), 'api')
            ->json('GET', $this->endpoint)
            ->assertOk()
            ->assertJsonHasPath('data.id')
            ->assertJsonHasPath('data.name')
            ->assertJsonHasPath('data.first_name')
            ->assertJsonHasPath('data.last_name')
            ->assertJsonHasPath('data.roles')
            ->assertJsonHasPath('data.permissions');
    }
}
