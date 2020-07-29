<?php

namespace Kirby\Novelties\Tests;

use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Novelties;
use Mockery;

/**
 * Class GetSettingsCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetSettingsCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/settings';

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function getShouldReturnOk(ApiTester $I)
    {
        $noveltiesMock = Mockery::mock(Novelties::class)
            ->shouldReceive('settings')
            ->andReturn(collect(['foo' => 'bar']))
            ->getMock();

        $I->haveInstance(Novelties::class, $noveltiesMock);

        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.foo');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        $novelty = factory(Novelty::class)->create();
        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);

        $I->sendGET($endpoint);

        $I->seeResponseCodeIs(403);
    }
}
