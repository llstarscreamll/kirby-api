<?php

namespace Authentication;

use Illuminate\Support\Facades\Hash;

/**
 * Class LoginCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LoginCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/auth/login';

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        config(['authentication.clients.web.id' => 1]);
        config(['authentication.clients.web.secret' => 'secret-token']);

        $I->haveRecord('oauth_clients', [
            'id'                     => 1,
            'name'                   => 'App Personal Access Client',
            'secret'                 => 'secret-token',
            'redirect'               => 'http://localhost',
            'personal_access_client' => 0,
            'password_client'        => 1,
            'revoked'                => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        $I->haveRecord('users', [
            'name'     => 'John Doe',
            'email'    => 'john@doe.com',
            'password' => Hash::make('123456'),
        ]);

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @param ApiTester $I
     */
    public function _after(ApiTester $I)
    {
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenCredentialsAreValidExpectOkWithAccessTokenAndRefreshToken(ApiTester $I)
    {
        $I->sendPOST($this->endpoint, [
            'email'    => 'john@doe.com',
            'password' => '123456',
        ]);

        $I->seeResponseCodeIs(200);

        $I->seeResponseJsonMatchesJsonPath('$.token_type');
        $I->seeResponseJsonMatchesJsonPath('$.expires_in');
        $I->seeResponseJsonMatchesJsonPath('$.access_token');
        $I->seeResponseJsonMatchesJsonPath('$.refresh_token');

        $I->seeCookie('accessToken');
        $I->seeCookie('refreshToken');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenEmailAndPasswordAreEmptyExpectUnprocesableEntity(ApiTester $I)
    {
        $I->sendPOST($this->endpoint, ['email' => '', 'password' => '']);

        $I->seeResponseCodeIs(422);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenPasswordDoesNotMatchExpectUnauthorizedWithMessageAndErrorOnResponse(ApiTester $I)
    {
        $I->sendPOST($this->endpoint, ['email' => 'foo@email.com', 'password' => '123456']);

        $I->seeResponseCodeIs(401);
        $I->seeResponseJsonMatchesJsonPath('$.error');
        $I->seeResponseJsonMatchesJsonPath('$.message');
    }
}
