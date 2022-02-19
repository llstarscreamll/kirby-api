<?php

namespace Kirby\Novelties\Tests\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\DTOs\SearchEmployeeNoveltiesData;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Jobs\GenerateCsvEmployeeResumeByNoveltyTypeJob;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\Novelties\Notifications\ExportNoveltiesResumeByTypeReady;
use Kirby\Users\Models\User;
use Tests\TestCase;

/**
 * @internal
 */
class GenerateCsvEmployeeResumeByNoveltyTypeJobTest extends TestCase
{
    private Employee $employee;
    private NoveltyType $additionNoveltyType;
    private NoveltyType $subtractNoveltyType;
    private NoveltyType $additionBalance;
    private NoveltyType $subtractBalance;

    public function setUp(): void
    {
        parent::setUp();

        $this->employee = factory(Employee::class)->create();
        $this->additionNoveltyType = factory(NoveltyType::class)->create(['operator' => NoveltyTypeOperator::Addition(), 'keep_in_report' => true]);
        $this->subtractNoveltyType = factory(NoveltyType::class)->create(['operator' => NoveltyTypeOperator::Subtraction(), 'keep_in_report' => true]);
        $this->additionBalance = factory(NoveltyType::class)->create(['code' => 'B+', 'operator' => NoveltyTypeOperator::Addition(), 'keep_in_report' => true]);
        $this->subtractBalance = factory(NoveltyType::class)->create(['code' => 'B-', 'operator' => NoveltyTypeOperator::Subtraction(), 'keep_in_report' => true]);

        DB::table('settings')->insert([
            [
                'key' => 'novelties.default-addition-balance-novelty-type',
                'name' => 'Default addition balance novelty type ', 'description' => '',
                'data_type' => 'int', 'value' => $this->additionBalance->id,
            ],
            [
                'key' => 'novelties.default-subtraction-balance-novelty-type',
                'name' => 'Default subtraction balance novelty type', 'description' => '',
                'data_type' => 'int', 'value' => $this->subtractBalance->id,
            ],
        ]);
    }

    /**
     * @test
     */
    public function shouldHaveCertainCsvHeaders()
    {
        $makeReportData = new SearchEmployeeNoveltiesData([
            'userId' => $this->employee->id,
            'employeeId' => 1,
            'startDate' => Carbon::parse('2020-01-01 00:00:00'),
            'endDate' => Carbon::parse('2020-01-05 23:59:59'),
        ]);

        Carbon::setTestNow('2020-01-10 10:05:59');

        $result = (new GenerateCsvEmployeeResumeByNoveltyTypeJob($makeReportData))->handle();

        $this->assertTrue($result);
        $this->assertTrue(Storage::disk('private')->exists('novelties/exports/2020-01-10_10_05_59.csv'));

        $fileRows = explode(PHP_EOL, Storage::disk('private')->get('novelties/exports/2020-01-10_10_05_59.csv'));

        $this->assertEquals(
            $fileRows[0],
            implode(';', [
                // employee data
                'Código', '"# de identificación"', 'Nombres',
                // addition novelties
                $this->additionBalance->code, $this->additionNoveltyType->code,
                // subtract novelties
                $this->subtractBalance->code, $this->subtractNoveltyType->code,
                'Total', // addition novelties - subtract novelties
            ])
        );
    }

    /**
     * @test
     */
    public function shouldHaveCsvWithCertainEmployeeData()
    {
        factory(Novelty::class)->create([
            'employee_id' => $this->employee,
            'novelty_type_id' => $this->additionNoveltyType,
            'start_at' => Carbon::parse('2020-01-01 10:00:00'),
            'end_at' => Carbon::parse('2020-01-01 12:00:00'),
        ]);

        $makeReportData = new SearchEmployeeNoveltiesData([
            'userId' => $this->employee->id,
            'startDate' => Carbon::parse('2020-01-01 00:00:00'),
            'endDate' => Carbon::parse('2020-01-05 23:59:59'),
        ]);

        Carbon::setTestNow('2020-01-10 10:05:59');

        $result = (new GenerateCsvEmployeeResumeByNoveltyTypeJob($makeReportData))->handle();

        $this->assertTrue($result);
        $this->assertTrue(Storage::disk('private')->exists('novelties/exports/2020-01-10_10_05_59.csv'));

        $fileRows = explode(PHP_EOL, Storage::disk('private')->get('novelties/exports/2020-01-10_10_05_59.csv'));

        $this->assertEquals(
            [
                $this->employee->code,
                $this->employee->identification_number,
                $this->employee->full_name,
            ],
            Arr::only(array_map(fn ($col) => trim($col, '"'), explode(';', $fileRows[1])), [0, 1, 2]),
        );
    }

    /**
     * @test
     */
    public function shouldHaveAllEmployeesDataDataWhenEmployeeIdAreNotSpecified()
    {
        factory(Novelty::class, 2)->create([
            'start_at' => Carbon::parse('2020-01-01 10:00:00'),
            'end_at' => Carbon::parse('2020-01-01 12:00:00'),
        ]);

        $makeReportData = new SearchEmployeeNoveltiesData([
            'userId' => $this->employee->id,
            'startDate' => Carbon::parse('2020-01-01 00:00:00'),
            'endDate' => Carbon::parse('2020-01-05 23:59:59'),
        ]);

        Carbon::setTestNow('2020-01-10 10:05:59');

        $result = (new GenerateCsvEmployeeResumeByNoveltyTypeJob($makeReportData))->handle();

        $this->assertTrue($result);
        $this->assertTrue(Storage::disk('private')->exists('novelties/exports/2020-01-10_10_05_59.csv'));

        $fileRows = explode(PHP_EOL, Storage::disk('private')->get('novelties/exports/2020-01-10_10_05_59.csv'));
        // 1 for headers + 2 from two employees created in this test + 1 employee created at setup
        $this->assertCount(4, array_filter($fileRows));
    }

    /**
     * @test
     */
    public function shouldHaveOnlySpecifiedEmployeeDataWhenEmployeeIdAreIsSpecified()
    {
        $novelties = factory(Novelty::class, 2)->create([
            'start_at' => Carbon::parse('2020-01-01 10:00:00'),
            'end_at' => Carbon::parse('2020-01-01 12:00:00'),
        ]);

        $makeReportData = new SearchEmployeeNoveltiesData([
            'userId' => $this->employee->id,
            'employeeId' => $novelties->first()->employee_id,
            'startDate' => Carbon::parse('2020-01-01 00:00:00'),
            'endDate' => Carbon::parse('2020-01-05 23:59:59'),
        ]);

        Carbon::setTestNow('2020-01-10 10:05:59');

        $result = (new GenerateCsvEmployeeResumeByNoveltyTypeJob($makeReportData))->handle();

        $this->assertTrue($result);
        $this->assertTrue(Storage::disk('private')->exists('novelties/exports/2020-01-10_10_05_59.csv'));

        $fileRows = explode(PHP_EOL, Storage::disk('private')->get('novelties/exports/2020-01-10_10_05_59.csv'));

        $this->assertCount(2, array_filter($fileRows)); // 1 for headers + 1 from specified employee
    }

    /**
     * @test
     */
    public function shouldCalculateCorrectlyEachNoveltyTypeHoursandTotal()
    {
        factory(Novelty::class)->create([
            'employee_id' => $this->employee,
            'novelty_type_id' => $this->additionNoveltyType,
            'start_at' => Carbon::parse('2020-01-01 10:00:00'),
            'end_at' => Carbon::parse('2020-01-01 12:00:00'),
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $this->employee,
            'novelty_type_id' => $this->additionNoveltyType,
            'start_at' => Carbon::parse('2020-01-03 08:00:00'),
            'end_at' => Carbon::parse('2020-01-03 09:30:00'),
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $this->employee,
            'novelty_type_id' => $this->subtractNoveltyType,
            'start_at' => Carbon::parse('2020-01-05 14:00:00'),
            'end_at' => Carbon::parse('2020-01-05 18:00:00'),
        ]);

        // this novelty will be out of data range, this novelty should be
        // ignored on generated report
        factory(Novelty::class)->create([
            'employee_id' => $this->employee,
            'novelty_type_id' => $this->subtractNoveltyType,
            'start_at' => Carbon::parse('2020-10-03 14:00:00'),
            'end_at' => Carbon::parse('2020-10-03 18:00:00'),
        ]);

        // next 4 hours should be ignored because they are from non reportable
        factory(Novelty::class)->create([
            'employee_id' => $this->employee,
            'novelty_type_id' => factory(NoveltyType::class)->create(['keep_in_report' => false, 'operator' => NoveltyTypeOperator::Subtraction()]),
            'start_at' => Carbon::parse('2020-01-03 14:00:00'),
            'end_at' => Carbon::parse('2020-01-03 18:00:00'),
        ]);

        // soft deleted novelties should be ignored
        factory(Novelty::class, 10)->create([
            'employee_id' => $this->employee,
            'novelty_type_id' => $this->subtractNoveltyType,
            'start_at' => Carbon::parse('2020-01-03 14:00:00'),
            'end_at' => Carbon::parse('2020-01-03 18:00:00'),
            'deleted_at' => now(),
        ]);

        $makeReportData = new SearchEmployeeNoveltiesData([
            'userId' => $this->employee->id,
            'employeeId' => $this->employee->id,
            'startDate' => Carbon::parse('2020-01-01 00:00:00'),
            'endDate' => Carbon::parse('2020-01-05 23:59:59'),
        ]);

        Carbon::setTestNow('2020-01-10 10:05:59');

        $result = (new GenerateCsvEmployeeResumeByNoveltyTypeJob($makeReportData))->handle();

        $this->assertTrue($result);
        $this->assertTrue(Storage::disk('private')->exists('novelties/exports/2020-01-10_10_05_59.csv'));

        $fileRows = explode(PHP_EOL, Storage::disk('private')->get('novelties/exports/2020-01-10_10_05_59.csv'));

        $this->assertEquals(
            [
                3 => '0', // non existing positive balance novelties, then 0
                4 => '3.5', // elapsed hours from first and second created addition novelties
                5 => '0', // non existing negative balance novelty, then 0
                6 => '-4', // elapsed hours from third created subtraction novelty
                7 => '-0.5', // = (0 + 2) + (0 + -4)
            ],
            Arr::except(explode(';', $fileRows[1]), [0, 1, 2]),
        );
    }

    /**
     * @test
     */
    public function shouldDispatchNotificationWhenCsvIsGeneratedSuccesfuly()
    {
        $makeReportData = new SearchEmployeeNoveltiesData([
            'userId' => $this->employee->id,
            'employeeId' => 1,
            'startDate' => Carbon::parse('2020-01-01 00:00:00'),
            'endDate' => Carbon::parse('2020-01-05 23:59:59'),
        ]);

        Carbon::setTestNow('2020-01-10 10:05:59');

        Notification::fake();
        $result = (new GenerateCsvEmployeeResumeByNoveltyTypeJob($makeReportData))->handle();

        $this->assertTrue($result);
        $this->assertTrue(Storage::disk('private')->exists('novelties/exports/2020-01-10_10_05_59.csv'));
        Notification::assertSentTo(
            User::find($this->employee->id),
            ExportNoveltiesResumeByTypeReady::class,
            fn ($notification) => Str::containsAll($notification->fileUrl, ['http', '/2020-01-10_10_05_59.csv'])
        );
    }
}
