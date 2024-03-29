<?php

namespace Kirby\Novelties\Tests\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Exports\NoveltiesExport;
use Kirby\Novelties\Jobs\GenerateCsvReportByEmployeeJob;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Notifications\NoveltiesExportReady;
use Kirby\Users\Contracts\UserRepositoryInterface;
use Kirby\Users\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use NoveltiesPackageSeed;

/**
 * Class GenerateCsvReportByEmployeeJobTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class GenerateCsvReportByEmployeeJobTest extends \Tests\TestCase
{
    /**
     * @test
     */
    public function shouldGenerateMonthlyReportSuccessfully()
    {
        $this->seed(NoveltiesPackageSeed::class);

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
            'start_at' => $startDate->copy()->subDays(2),
            'end_at' => $startDate->copy()->subDays(2)->addHours(2),
        ]);

        factory(Novelty::class, 2)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $zeroScc->id,
            'start_at' => $endDate->copy()->addDays(2),
            'end_at' => $endDate->copy()->addDays(2)->addHours(2),
        ]);

        // in range novelties
        $oldestNovelties = factory(Novelty::class, 4)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $firstScc->id,
            'start_at' => $startDate,
            'end_at' => $startDate->copy()->addHours(2),
        ]);

        // in range novelties
        $latestNovelties = factory(Novelty::class, 6)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $secondScc->id,
            'start_at' => $startDate->copy()->addDays(10),
            'end_at' => $startDate->copy()->addDays(10)->addHours(2),
            'comment' => 'foo',
        ]);

        // attach same approvers to $latestNovelties
        $approvers = factory(User::class, 2)->create();
        $latestNovelties->each(fn ($novelty) => $novelty->approvals()->sync($approvers));

        $user = factory(User::class)->create();
        $params = [
            'employee_id' => $tonyStark->id,
            'time_clock_log_check_out_start_date' => $startDate->toDateTimeString(),
            'time_clock_log_check_out_end_date' => $endDate->toDateTimeString(),
        ];

        $userRepositoryMock = $this->mock(UserRepositoryInterface::class)
            ->shouldReceive('find')
            ->with($user->id)
            ->andReturn($user)
            ->getMock();

        Carbon::setTestNow('2022-06-24 10:10:10');
        Excel::fake();
        Notification::fake();

        $job = new GenerateCsvReportByEmployeeJob($user->id, $params);
        $result = $job->handle($userRepositoryMock);

        $this->assertTrue($result);
        $expectedFile = sprintf('novelties/exports/novelties_%s.csv', str_replace([' ', ':'], ['_', ''], now()->toDateTimeString()));
        $expectedFile = str_replace([' ', ':'], ['_', '-'], $expectedFile);
        Excel::assertStored($expectedFile, 'public', function (NoveltiesExport $export) use ($oldestNovelties, $latestNovelties) {
            $this->assertCount($oldestNovelties->count() + $latestNovelties->count(), $export->query()->get());

            return true;
        });
        Notification::assertSentTo($user, NoveltiesExportReady::class);
    }
}
