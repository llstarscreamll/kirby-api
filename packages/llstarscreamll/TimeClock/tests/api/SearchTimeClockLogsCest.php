<?php

namespace ClockTime;

use llstarscreamll\TimeClock\Models\TimeClockLog;

/**
 * Class SearchTimeClockLogsCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchTimeClockLogsCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock-logs';

    /**
     * @var \llstarscreamll\Users\Models\User
     */
    private $user;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->disableMiddleware();
        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');

        // time clock logs
        factory(TimeClockLog::class, 2)->create();
    }

    /**
     * @param ApiTester $I
     */
    public function test(ApiTester $I)
    {
        $I->sendGET($this->endpoint);
        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0');
        $I->seeResponseJsonMatchesJsonPath('$.data.1');
        // relations
        $I->seeResponseJsonMatchesJsonPath('$.data.1.novelties');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.employee.user');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.work_shift');
    }
}
