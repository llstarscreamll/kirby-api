<?php

namespace WorkShifts;

/**
 * Class UpdateWorkShiftByIdCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UpdateWorkShiftByIdCest
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
     * @var array
     */
    private $requestData = [
        'name' => 'updated work shift',
        'grace_minutes_before_start_times' => 45,
        'grace_minutes_after_start_times' => 45,
        'grace_minutes_before_end_times' => 45,
        'grace_minutes_after_end_times' => 45,
        'meal_time_in_minutes' => 45,
        'min_minutes_required_to_discount_meal_time' => 30 * 2,
        'time_slots' => [['start' => '07:00', 'end' => '12:30']],
    ];

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->workShift = [
            'name' => 'work shift A',
            'grace_minutes_before_start_times' => 15,
            'grace_minutes_after_start_times' => 15,
            'grace_minutes_before_end_times' => 15,
            'grace_minutes_after_end_times' => 15,
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
    public function whenIdExistsAndRequestDataIsValidExpectOkWithResourceUpdatedInResponseAndDB(ApiTester $I)
    {
        $I->sendPUT(str_replace(':id', $this->workShift['id'], $this->endpoint), $this->requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeResponseContainsJson(['data' => ['id' => $this->workShift['id']]]);
        $I->seeRecord('work_shifts', array_except($this->requestData, 'time_slots'));
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenIdDoesNotExistsExpectNotFound(ApiTester $I)
    {
        $I->sendPUT(str_replace(':id', 123, $this->endpoint), $this->requestData);

        $I->seeResponseCodeIs(404);
    }
}
