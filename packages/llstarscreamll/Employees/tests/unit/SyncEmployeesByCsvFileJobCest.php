<?php

namespace Employees;

use Employees\UnitTester;
use Illuminate\Support\Facades\Artisan;
use llstarscreamll\Company\Models\CostCenter;
use llstarscreamll\Users\Contracts\UserRepositoryInterface;
use llstarscreamll\Employees\Jobs\SyncEmployeesByCsvFileJob;
use llstarscreamll\Employees\Contracts\EmployeeRepositoryInterface;
use llstarscreamll\WorkShifts\Data\Seeders\DefaultWorkShiftsSeeder;
use llstarscreamll\WorkShifts\Contracts\WorkShiftRepositoryInterface;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;

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
        // create default work shifts
        Artisan::call('db:seed', ['--class' => DefaultWorkShiftsSeeder::class]);
    }

    /**
     * @test
     * @param UnitTester $I
     */
    public function syncValidFileWithTwoEmployees(UnitTester $I)
    {
        $costCenters = factory(CostCenter::class, 2)->create();
        $filePath = codecept_data_dir('import_employees/good_employees.csv');
        $userRepository = app(UserRepositoryInterface::class);
        $employeeRepository = app(EmployeeRepositoryInterface::class);
        $identificationRepository = app(IdentificationRepositoryInterface::class);
        $workShiftRepository = app(WorkShiftRepositoryInterface::class);

        $job = new SyncEmployeesByCsvFileJob($filePath);
        $I->assertTrue($job->handle(
            $userRepository, $employeeRepository, $workShiftRepository, $identificationRepository
        ));

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
            'id' => 1,
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
            'id' => 2,
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
            'employee_id' => 1,
            'name' => 'E-card',
            'code' => 'Code-1',
        ]);

        $I->seeRecord('identifications', [
            'employee_id' => 1,
            'name' => 'PIN',
            'code' => '2369',
        ]);

        $I->seeRecord('identifications', [
            'employee_id' => 2,
            'name' => 'E-card',
            'code' => 'Code-3',
        ]);

        // employee work shifts persisted on DB
        $I->seeRecord('employee_work_shift', [
            'employee_id' => 1,
            'work_shift_id' => 1,
        ]);

        $I->seeRecord('employee_work_shift', [
            'employee_id' => 1,
            'work_shift_id' => 2,
        ]);

        $I->seeRecord('employee_work_shift', [
            'employee_id' => 1,
            'work_shift_id' => 3,
        ]);

        $I->seeRecord('employee_work_shift', [
            'employee_id' => 2,
            'work_shift_id' => 1,
        ]);
    }
}
