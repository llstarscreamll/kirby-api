<?php

namespace WorkShifts;

/**
 * Class GetWorkShiftByIdCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetWorkShiftByIdCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/work-shifts/:id';

    /**
     * @var array
     */
    private $workShift;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->workShift = [
            'name' => 'work shift A',
            'grace_minutes_for_start_times' => 15,
            'grace_minutes_for_end_times' => 15,
            'meal_time_in_minutes' => 90,
            'min_minutes_required_to_discount_meal_time' => 60 * 6,
            'time_slots' => json_encode([['start' => '07:00', 'end' => '12:30'], ['start' => '02:00', 'end' => '06:00']]),
        ];

        $this->workShift['id'] = $I->haveRecord('work_shifts', $this->workShift);

        $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenIdExistsExpectOkWithSaidResourceAsJson(ApiTester $I)
    {
        $I->sendGET(str_replace(':id', $this->workShift['id'], $this->endpoint));

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeResponseContainsJson(['data' => ['id' => $this->workShift['id']]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenIdDoesNotExistsExpectNotFound(ApiTester $I)
    {
        $I->sendGET(str_replace(':id', 123, $this->endpoint));

        $I->seeResponseCodeIs(404);
    }
}
