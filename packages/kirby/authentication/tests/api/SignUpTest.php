<?php

namespace Kirby\Authentication\Tests\api;

/**
 * Class SignUpTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SignUpTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/auth/sign-up';

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
    }

    /**
     * @test
     */
    public function whenRequestDataIsValidExpectOkWithAccessAndRefreshTokensAndUserToBeCreated()
    {
        $this->json('POST', $this->endpoint, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '+573119876543',
            'email' => 'john@doe.com',
            'password' => '123456',
            'password_confirmation' => '123456',
        ])
            ->assertOk()
            ->assertJsonHasPath('token_type')
            ->assertJsonHasPath('expires_in')
            ->assertJsonHasPath('access_token')
            ->assertJsonHasPath('refresh_token')
            ->assertCookie('accessToken')
            ->assertCookie('refreshToken');

        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'phone_number' => '+573119876543',
        ]);
    }

    /**
     * @test
     */
    public function whenFieldsAreEmptyExpectUnprocesableEntity()
    {
        $this->json('POST', $this->endpoint, ['name' => '', 'email' => '', 'password' => ''])
            ->assertStatus(422);
    }

    /**
     * @test
     */
    public function whenGivenEmailIsAlreadyTakenByAnotherUserExpectUnprocesableEntity()
    {
        $this->haveRecord('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '+573216549879',
            'email' => 'john@doe.com',
            'password' => bcrypt('123456'),
        ]);

        $this->json('POST', $this->endpoint, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '+573129876543',
            'email' => 'john@doe.com',
            'password' => '123456',
            'password_confirmation' => '123456',
        ])->assertStatus(422);
    }
}
