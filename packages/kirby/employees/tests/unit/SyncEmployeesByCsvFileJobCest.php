<?php

namespace Employees;

use DefaultWorkShiftsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Kirby\Company\Contracts\CostCenterRepositoryInterface;
use Kirby\Company\Models\CostCenter;
use Kirby\Employees\Contracts\EmployeeRepositoryInterface;
use Kirby\Employees\Contracts\IdentificationRepositoryInterface;
use Kirby\Employees\Jobs\SyncEmployeesByCsvFileJob;
use Kirby\Employees\Models\Employee;
use Kirby\Employees\Models\Identification;
use Kirby\Employees\Notifications\FailedEmployeesSyncNotification;
use Kirby\Employees\Notifications\SuccessfulEmployeesSyncNotification;
use Kirby\Users\Contracts\UserRepositoryInterface;
use Kirby\Users\Models\User;
use Kirby\WorkShifts\Contracts\WorkShiftRepositoryInterface;

/**
 * Class SyncEmployeesByCsvFileJobCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SyncEmployeesByCsvFileJobCest
{
    /**
     * @param UnitTester $I
     */
    public function _before(UnitTester $I)
    {
        Storage::delete('employees_sync/test_file.csv');
        // create default work shifts
        Artisan::call('db:seed', ['--class' => DefaultWorkShiftsSeeder::class]);
    }

    /**
     * @param string $fileName
     */
    private function putTestFile(string $fileName)
    {
        $filePath = codecept_data_dir("import_employees/{$fileName}");
        File::copy($filePath, storage_path("app/employees_sync/{$fileName}"));

        return "employees_sync/{$fileName}";
    }

    /**
     * @test
     * @param UnitTester $I
     */
    public function syncValidFileWithTwoEmployees(UnitTester $I)
    {
        // create fake data
        $user = factory(User::class)->create();
        $employee = factory(Employee::class)->create();
        $costCenters = factory(CostCenter::class, 2)->create();

        $userRepository = app(UserRepositoryInterface::class);
        $employeeRepository = app(EmployeeRepositoryInterface::class);
        $workShiftRepository = app(WorkShiftRepositoryInterface::class);
        $costCenterRepository = app(CostCenterRepositoryInterface::class);
        $identificationRepository = app(IdentificationRepositoryInterface::class);

        // file to test
        $filePath = $this->putTestFile('good_employees.csv');

        Notification::fake();

        $job = new SyncEmployeesByCsvFileJob($user->id, $filePath);
        $I->assertTrue($job->handle(
            $userRepository, $employeeRepository,
            $workShiftRepository, $costCenterRepository,
            $identificationRepository
        ));

        // success notification should have been sent
        Notification::assertSentTo($user, SuccessfulEmployeesSyncNotification::class);

        // users persisted on DB
        $I->seeRecord('users', [
            'first_name' => 'Tony',
            'last_name' => 'Stark',
            'email' => 'tony@stark.com',
        ]);

        $I->seeRecord('users', [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'email' => 'bruce@banner.com',
        ]);

        // cost centers persisted on DB
        $I->seeRecord('cost_centers', [
            'code' => 'cc1',
            'name' => 'centro de costos uno',
        ]);

        $I->seeRecord('cost_centers', [
            'code' => 'cc2',
            'name' => 'centro de costos dos',
        ]);

        // cost centers not present on csv file trashed
        $costCenter = $I->grabRecord('cost_centers', ['id' => $costCenters->first()->id]);
        $I->assertNotNull($costCenter['deleted_at']);
        $costCenter = $I->grabRecord('cost_centers', ['id' => $costCenters->last()->id]);
        $I->assertNotNull($costCenter['deleted_at']);

        // work shifts persisted on DB
        $I->seeRecord('work_shifts', ['name' => '07-18']);
        $workShift = $I->grabRecord('work_shifts', ['name' => '07-18']);
        $I->assertEquals(json_decode($workShift['time_slots'], true), [
            ['start' => '07:00', 'end' => '12:30'],
            ['start' => '13:30', 'end' => '18:00'],
        ]);

        // work shift not present on csv file should be trashed
        $trashedWorkShift = $I->grabRecord('work_shifts', ['name' => '22-06']);
        $I->assertNotNull($trashedWorkShift['deleted_at']);

        // employees data persisted on DB, user may has related employee data
        $I->seeRecord('employees', [
            'id' => 3,
            'code' => '123',
            'identification_number' => '456',
            'cost_center_id' => 3, // newly created cost center
            'position' => 'developer',
            'location' => 'Bogotá',
            'address' => 'Calle 1#2-3',
            'phone' => '3111234567',
            'salary' => 10000000,
        ]);

        $I->seeRecord('employees', [
            'id' => 4,
            'code' => '987',
            'identification_number' => '654',
            'cost_center_id' => 4, // newly created cost center
            'position' => 'designer',
            'location' => 'Medellín',
            'address' => 'Calle 3#2-1',
            'phone' => '3219876543',
            'salary' => 5000000,
        ]);

        // employees not present on csv file should be trashed
        $trashedEmployee = $I->grabRecord('employees', ['id' => $employee->id]);
        $I->assertNotNull($trashedEmployee['deleted_at']);

        // identifications codes persisted on DB
        $I->seeRecord('identifications', [
            'employee_id' => 3,
            'name' => 'E-card',
            'code' => 'Code-1',
        ]);

        $I->seeRecord('identifications', [
            'employee_id' => 3,
            'name' => 'PIN',
            'code' => '2369',
        ]);

        $I->seeRecord('identifications', [
            'employee_id' => 4,
            'name' => 'E-card',
            'code' => 'Code-3',
        ]);

        // employee work shifts persisted on DB
        $I->seeRecord('employee_work_shift', [
            'employee_id' => 3,
            'work_shift_id' => 1,
        ]);

        $I->seeRecord('employee_work_shift', [
            'employee_id' => 3,
            'work_shift_id' => 2,
        ]);

        $I->seeRecord('employee_work_shift', [
            'employee_id' => 4,
            'work_shift_id' => 4,
        ]);
    }

    /**
     * @test
     * @param UnitTester $I
     */
    public function syncValidFileWithIdentificationCodeTakenByAnotherEmployee(UnitTester $I)
    {
        $user = factory(User::class)->create();
        factory(Identification::class)->create(['code' => 'Code-1']);
        factory(CostCenter::class, 2)->create();

        // test file
        $filePath = $this->putTestFile('good_employees.csv');

        $userRepository = app(UserRepositoryInterface::class);
        $employeeRepository = app(EmployeeRepositoryInterface::class);
        $workShiftRepository = app(WorkShiftRepositoryInterface::class);
        $costCenterRepository = app(CostCenterRepositoryInterface::class);
        $identificationRepository = app(IdentificationRepositoryInterface::class);

        Notification::fake();

        $job = new SyncEmployeesByCsvFileJob($user->id, $filePath);
        $I->assertFalse($job->handle(
            $userRepository, $employeeRepository,
            $workShiftRepository, $costCenterRepository,
            $identificationRepository
        ));

        // error notification should have been sent
        Notification::assertSentTo($user, FailedEmployeesSyncNotification::class);
    }
}
