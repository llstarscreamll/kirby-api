<?php
namespace ClockTime;

use ClockTime\ApiTester;
use llstarscreamll\Users\Models\User;

/**
 * Class CreateTimeClockLogCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateTimeClockLogCest
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
        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldRegisterEmployeeClockIn(ApiTester $I)
    {
        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->create();

        $requestData = [
            'action'            => 'clock_in',
            'employee_identity' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath("$.data.id");
        $I->seeRecord('time_clock_logs', [
            'employee_id'      => $employee->id,
            'checked_in_at'    => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }
}
