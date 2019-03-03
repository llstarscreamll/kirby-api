<?php
namespace Authentication;

use Authentication\ApiTester;

/**
 * Class SignUpCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SignUpCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/sign-up';

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

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @param ApiTester $I
     */
    public function _after(ApiTester $I) {}

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenRequestDataIsValidExpectOkWithAccessAndRefreshTokensAndUserTobeCreated(ApiTester $I)
    {
        $I->sendPOST($this->endpoint, [
            'name'                  => 'John Doe',
            'email'                 => 'john@doe.com',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $I->seeResponseCodeIs(200);

        $I->seeResponseJsonMatchesJsonPath("$.token_type");
        $I->seeResponseJsonMatchesJsonPath("$.expires_in");
        $I->seeResponseJsonMatchesJsonPath("$.access_token");
        $I->seeResponseJsonMatchesJsonPath("$.refresh_token");

        $I->seeCookie('accessToken');
        $I->seeCookie('refreshToken');

        $I->seeRecord('users', [
            'name'  => 'John Doe',
            'email' => 'john@doe.com',
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenFieldsAreEmptyExpectUnprocesableEntity(ApiTester $I)
    {
        $I->sendPOST($this->endpoint, ['name' => '', 'email' => '', 'password' => '']);

        $I->seeResponseCodeIs(422);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenGivenEmailIsAlreadyTakenByAnotherUserExpectUnprocesableEntity(ApiTester $I)
    {
        $I->haveRecord('users', [
            'name'     => 'John Doe',
            'email'    => 'john@doe.com',
            'password' => bcrypt('123456'),
        ]);

        $I->sendPOST($this->endpoint, [
            'name'                  => 'John Doe',
            'email'                 => 'john@doe.com',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        $I->seeResponseCodeIs(422);
    }
}
