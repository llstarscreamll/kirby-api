<?php

namespace WorkShifts;

/**
 * Class CreateWorkShiftCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateWorkShiftCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/work-shifts';

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenRequestDataIsValidExpectCreatedWithResourceOnResponseAndDB(ApiTester $I)
    {
        $requestData = [
            'name'                                       => 'work shift one',
            'start_time'                                 => '07:00',
            'end_time'                                   => '14:00',
            'grace_minutes_for_start_time'               => 15,
            'grace_minutes_for_end_time'                 => 15,
            'meal_time_in_minutes'                       => 90,
            'min_minutes_required_to_discount_meal_time' => 60 * 6,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('work_shifts', $requestData);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenRequestDataIsEmptyExpectUnprocesableEntity(ApiTester $I)
    {
        $I->sendPOST($this->endpoint, []);

        $I->seeResponseCodeIs(422);
    }
}
