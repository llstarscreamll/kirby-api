<?php

namespace WorkShifts;

/**
 * Class SearchWorkShiftsCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchWorkShiftsCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/work-shifts';

    /**
     * @var array
     */
    private $workShiftA;

    /**
     * @var array
     */
    private $workShiftB;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->workShiftA = [
            'name' => 'work shift A',
            'grace_minutes_for_start_times' => 15,
            'grace_minutes_for_end_times' => 15,
            'meal_time_in_minutes' => 90,
            'min_minutes_required_to_discount_meal_time' => 60 * 6,
            'time_slots' => json_encode([['start' => '07:00', 'end' => '12:30']]),
        ];

        $this->workShiftB = [
            'name' => 'work shift B',
            'grace_minutes_for_start_times' => 30,
            'grace_minutes_for_end_times' => 30,
            'meal_time_in_minutes' => 30,
            'min_minutes_required_to_discount_meal_time' => 60 * 4,
            'time_slots' => json_encode([['start' => '18:00', 'end' => '02:00']]),
        ];

        $I->haveRecord('work_shifts', $this->workShiftA);
        $I->haveRecord('work_shifts', $this->workShiftB);

        $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenRequestDataIsEmptyExpectOkWithAllResourcesPaginated(ApiTester $I)
    {
        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0');
        $I->seeResponseJsonMatchesJsonPath('$.data.1');
        $I->seeResponseJsonMatchesJsonPath('$.meta');
        $I->seeResponseJsonMatchesJsonPath('$.links');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenRequestDataHasSearchKeyOnFilterFieldExpectOkWithResourcesContainingTheGivenValueInTheNameAttribue(ApiTester $I)
    {
        $I->sendGET($this->endpoint, ['filter' => ['search' => $this->workShiftA['name']]]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.1');
        $I->seeResponseJsonMatchesJsonPath('$.meta');
        $I->seeResponseJsonMatchesJsonPath('$.links');
    }
}
