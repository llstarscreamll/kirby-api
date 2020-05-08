<?php

namespace Novelties\Actions;

use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Actions\GenerateReportByEmployee;
use Kirby\Novelties\Models\Novelty;
use Kirby\Users\Models\User;
use Novelties\IntegrationTester;

/**
 * Class GenerateReportByEmployeeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GenerateReportByEmployeeCest
{
    /**
     * @param IntegrationTester $I
     */
    public function _before(IntegrationTester $I)
    {
        //
    }

    /**
     * @param IntegrationTester $I
     */
    public function shouldGenerateMonthlyReportSuccessfully(IntegrationTester $I)
    {
        // report range dates
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $tonyStark = factory(Employee::class)->create();

        // sub cost centers
        $zeroScc = factory(SubCostCenter::class)->create(['name' => '000']);
        $firstScc = factory(SubCostCenter::class)->create(['name' => 'AAA']);
        $secondScc = factory(SubCostCenter::class)->create(['name' => 'BBB']);

        // out of range novelties
        factory(Novelty::class, 2)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $zeroScc->id,
            'scheduled_start_at' => $startDate->copy()->subDays(2),
            'scheduled_end_at' => $startDate->copy()->subDays(2)->addHours(2),
        ]);

        factory(Novelty::class, 2)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $zeroScc->id,
            'scheduled_start_at' => $endDate->copy()->addDays(2),
            'scheduled_end_at' => $endDate->copy()->addDays(2)->addHours(2),
        ]);

        // in range novelties
        $oldestNovelties = factory(Novelty::class, 4)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $firstScc->id,
            'scheduled_start_at' => $startDate,
            'scheduled_end_at' => $startDate->copy()->addHours(2),
        ]);

        // in range novelties
        $latestNovelties = factory(Novelty::class, 6)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $secondScc->id,
            'scheduled_start_at' => $startDate->copy()->addDays(10),
            'scheduled_end_at' => $startDate->copy()->addDays(10)->addHours(2),
            'comment' => 'foo',
        ]);

        // attach same approvers to $latestNovelties
        $approvers = factory(User::class, 2)->create();
        $latestNovelties->each(fn ($novelty) => $novelty->approvals()->sync($approvers));

        $action = app(GenerateReportByEmployee::class);
        $result = $action->run($tonyStark->id, $startDate, $endDate);

        // first row results
        $I->assertEquals($startDate->copy()->addDays(10)->startOfDay()->toISOString(), $result[0]['date']);
        // employee
        $I->assertEquals($tonyStark->id, $result[0]['employee']['id']);
        $I->assertArrayHasKey('id', $result[0]['employee']);
        $I->assertArrayHasKey('cost_center_id', $result[0]['employee']);
        $I->assertArrayHasKey('code', $result[0]['employee']);
        $I->assertArrayHasKey('identification_number', $result[0]['employee']);
        $I->assertArrayHasKey('position', $result[0]['employee']);
        $I->assertArrayHasKey('location', $result[0]['employee']);
        $I->assertArrayHasKey('address', $result[0]['employee']);
        $I->assertArrayHasKey('phone', $result[0]['employee']);
        $I->assertArrayHasKey('salary', $result[0]['employee']);
        $I->assertArrayHasKey('id', $result[0]['employee']['user']);
        $I->assertArrayHasKey('email', $result[0]['employee']['user']);
        $I->assertArrayHasKey('first_name', $result[0]['employee']['user']);
        $I->assertArrayHasKey('last_name', $result[0]['employee']['user']);
        $I->assertArrayNotHasKey('password', $result[0]['employee']['user']);
        $I->assertArrayNotHasKey('created_at', $result[0]['employee']['user']);
        $I->assertArrayNotHasKey('updated_at', $result[0]['employee']['user']);
        // novelties
        $I->assertEquals($latestNovelties->count(), count($result[0]['novelties']));
        $latestNovelties->each(fn ($novelty, $i) => $I->assertEquals($novelty->id, $result[0]['novelties'][$i]['id']));
        array_walk($result[0]['novelties'], function ($novelty) use ($I) {
            $this->assertNoveltyHasKeys($I, $novelty);
        });

        // second row results
        $I->assertEquals($startDate->startOfDay()->toISOString(), $result[1]['date']);
        $I->assertEquals($tonyStark->id, $result[1]['employee']['id']);
        //  novelties
        $I->assertEquals($oldestNovelties->count(), count($result[1]['novelties']));
        $oldestNovelties->each(fn ($novelty, $i) => $I->assertEquals($novelty->id, $result[1]['novelties'][$i]['id']));
        array_walk($result[1]['novelties'], function ($novelty) use ($I) {
            $this->assertNoveltyHasKeys($I, $novelty);
        });
    }

    private function assertNoveltyHasKeys(IntegrationTester $I, array $result)
    {
        $I->assertArrayHasKey('id', $result);
        $I->assertArrayHasKey('employee', $result);
        $I->assertArrayHasKey('sub_cost_center', $result);
        $I->assertArrayHasKey('cost_center', $result['sub_cost_center']);
        $I->assertArrayHasKey('novelty_type', $result);
        $I->assertArrayHasKey('approvals', $result);
    }
}
