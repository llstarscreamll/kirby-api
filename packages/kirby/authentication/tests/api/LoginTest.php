<?php

namespace Kirby\Authentication\Tests\api;

use Illuminate\Support\Facades\Hash;

/**
 * Class LoginTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LoginTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/auth/login';

    public function setUp(): void
    {
        parent::setUp();
        config(['authentication.clients.web.id' => 1]);
        config(['authentication.clients.web.secret' => 'secret-token']);

        $this->haveRecord('oauth_clients', [
            'id' => 1,
            'name' => 'App Personal Access Client',
            'secret' => 'secret-token',
            'redirect' => 'http://localhost',
            'personal_access_client' => 0,
            'password_client' => 1,
            'revoked' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->haveRecord('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'password' => Hash::make('123456'),
        ]);
    }

    /**
     * @test
     */
    public function whenCredentialsAreValidExpectOkWithAccessTokenAndRefreshToken()
    {
        $this->json('POST', $this->endpoint, ['email' => 'john@doe.com', 'password' => '123456'])
            ->assertOk()
            ->assertJsonHasPath('token_type')
            ->assertJsonHasPath('expires_in')
            ->assertJsonHasPath('access_token')
            ->assertJsonHasPath('refresh_token')
            ->assertCookie('accessToken')
            ->assertCookie('refreshToken');
    }

    /**
     * @test
     */
    public function whenEmailAndPasswordAreEmptyExpectUnprocesableEntity()
    {
        $this->json('POST', $this->endpoint, ['email' => '', 'password' => ''])
            ->assertStatus(422);
    }

    /**
     * @test
     */
    public function whenPasswordDoesNotMatchExpectUnauthorizedWithMessageAndErrorOnResponse()
    {
        $this->json('POST', $this->endpoint, ['email' => 'foo@email.com', 'password' => '123456'])
            ->assertStatus(401)
            ->assertJsonHasPath('error')
            ->assertJsonHasPath('message');
    }
}
