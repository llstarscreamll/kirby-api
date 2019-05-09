<?php

namespace Employees;

use Illuminate\Support\Facades\File;
use llstarscreamll\Users\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use llstarscreamll\Company\Models\CostCenter;
use llstarscreamll\Employees\Models\Identification;
use llstarscreamll\Users\Contracts\UserRepositoryInterface;
use llstarscreamll\Employees\Jobs\SyncEmployeesByCsvFileJob;
use llstarscreamll\Employees\Contracts\EmployeeRepositoryInterface;
use llstarscreamll\WorkShifts\Data\Seeders\DefaultWorkShiftsSeeder;
use llstarscreamll\WorkShifts\Contracts\WorkShiftRepositoryInterface;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\Employees\Notifications\FailedEmployeesSyncNotification;
use llstarscreamll\Employees\Notifications\SuccessfulEmployeesSyncNotification;

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
    private function placeFile(string $fileName)
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
        $user = factory(User::class)->create();
        $costCenters = factory(CostCenter::class, 2)->create();
        $userRepository = app(UserRepositoryInterface::class);
        $employeeRepository = app(EmployeeRepositoryInterface::class);
        $workShiftRepository = app(WorkShiftRepositoryInterface::class);
        $identificationRepository = app(IdentificationRepositoryInterface::class);
        $filePath = $this->placeFile('good_employees.csv');

        Notification::fake();

        $job = new SyncEmployeesByCsvFileJob($user->id, $filePath);
        $I->assertTrue($job->handle(
            $userRepository, $employeeRepository, $workShiftRepository, $identificationRepository
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

        // employees data persisted on DB, user may has related employee data
        $I->seeRecord('employees', [
            'id' => 2,
            'code' => '123',
            'identification_number' => '456',
            'cost_center_id' => 1,
            'position' => 'developer',
            'location' => 'Bogotá',
            'address' => 'Calle 1#2-3',
            'phone' => '3111234567',
            'salary' => 10000000,
        ]);

        $I->seeRecord('employees', [
            'id' => 3,
            'code' => '987',
            'identification_number' => '654',
            'cost_center_id' => 2,
            'position' => 'designer',
            'location' => 'Medellín',
            'address' => 'Calle 3#2-1',
            'phone' => '3219876543',
            'salary' => 5000000,
        ]);

        // identifications codes persisted on DB
        $I->seeRecord('identifications', [
            'employee_id' => 2,
            'name' => 'E-card',
            'code' => 'Code-1',
        ]);

        $I->seeRecord('identifications', [
            'employee_id' => 2,
            'name' => 'PIN',
            'code' => '2369',
        ]);

        $I->seeRecord('identifications', [
            'employee_id' => 3,
            'name' => 'E-card',
            'code' => 'Code-3',
        ]);

        // employee work shifts persisted on DB
        $I->seeRecord('employee_work_shift', [
            'employee_id' => 2,
            'work_shift_id' => 1,
        ]);

        $I->seeRecord('employee_work_shift', [
            'employee_id' => 2,
            'work_shift_id' => 2,
        ]);

        $I->seeRecord('employee_work_shift', [
            'employee_id' => 2,
            'work_shift_id' => 3,
        ]);

        $I->seeRecord('employee_work_shift', [
            'employee_id' => 3,
            'work_shift_id' => 1,
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
        $costCenters = factory(CostCenter::class, 2)->create();
        $filePath = $this->placeFile('good_employees.csv');

        $userRepository = app(UserRepositoryInterface::class);
        $employeeRepository = app(EmployeeRepositoryInterface::class);
        $identificationRepository = app(IdentificationRepositoryInterface::class);
        $workShiftRepository = app(WorkShiftRepositoryInterface::class);

        Notification::fake();

        $job = new SyncEmployeesByCsvFileJob($user->id, $filePath);
        $I->assertFalse($job->handle(
            $userRepository, $employeeRepository, $workShiftRepository, $identificationRepository
        ));

        // error notification should have been sent
        Notification::assertSentTo($user, FailedEmployeesSyncNotification::class);
    }
}
