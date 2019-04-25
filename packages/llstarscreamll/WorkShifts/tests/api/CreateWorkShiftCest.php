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
        $requestBody = [
            'name' => 'work shift one',
            'grace_minutes_for_start_times' => 15,
            'grace_minutes_for_end_times' => 15,
            'meal_time_in_minutes' => 90,
            'min_minutes_required_to_discount_meal_time' => 60 * 6,
            'time_slots' => [
                ['start' => '07:00', 'end' => '12:30'],
                ['start' => '02:00', 'end' => '06:00'],
            ],
        ];

        $I->sendPOST($this->endpoint, $requestBody);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('work_shifts', array_except($requestBody, 'time_slots'));
        $record = $I->grabRecord('work_shifts', array_except($requestBody, 'time_slots'));
        $I->assertEquals(array_get($requestBody, 'time_slots'), json_decode($record['time_slots'], true));
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
