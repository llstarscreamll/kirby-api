<?php

namespace Kirby\Employees\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Kirby\Company\Contracts\CostCenterRepositoryInterface;
use Kirby\Company\Models\CostCenter;
use Kirby\Employees\Contracts\EmployeeRepositoryInterface;
use Kirby\Employees\Contracts\IdentificationRepositoryInterface;
use Kirby\Employees\Notifications\FailedEmployeesSyncNotification;
use Kirby\Employees\Notifications\SuccessfulEmployeesSyncNotification;
use Kirby\Users\Contracts\UserRepositoryInterface;
use Kirby\WorkShifts\Contracts\WorkShiftRepositoryInterface;
use League\Csv\Reader;
use League\Csv\Statement;

/**
 * Class SyncEmployeesByCsvFileJob.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SyncEmployeesByCsvFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 3;

    /**
     * @var string
     */
    private $csvFilePath;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var array
     */
    private $fileColumns = [
        'code',
        'identification_number',
        'first_name',
        'last_name',
        'cost_center',
        'position',
        'location',
        'address',
        'phone',
        'email',
        'salary',
        'identifications',
        'work_shifts',
    ];

    /**
     * @var Illuminate\Support\Collection
     */
    private $employees;

    /**
     * @var Illuminate\Support\Collection
     */
    private $costCenters;

    /**
     * @var Illuminate\Support\Collection
     */
    private $workShifts;

    /**
     * Create a new job instance.
     *
     * @param int    $userId
     * @param string $csvFilePath
     */
    public function __construct(int $userId, string $csvFilePath)
    {
        $this->userId = $userId;
        $this->csvFilePath = $csvFilePath;
        $this->employees = new Collection();
        $this->costCenters = new Collection();
        $this->workShifts = new Collection();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        UserRepositoryInterface $userRepository,
        EmployeeRepositoryInterface $employeeRepository,
        WorkShiftRepositoryInterface $workShiftRepository,
        CostCenterRepositoryInterface $costCenterRepository,
        IdentificationRepositoryInterface $identificationRepository
    ) {
        $reader = Reader::createFromPath(storage_path("app/{$this->csvFilePath}"), 'r')->setDelimiter(';');
        $records = (new Statement())
            ->offset(1)
            ->process($reader, $this->fileColumns);

        try {
            foreach ($records as $record) {
                $record = array_map('trim', $record);
                // store cost center
                $costCenter = $this->storeCostCenter($record['cost_center'], $costCenterRepository);

                $record['cost_center_id'] = $costCenter->id;

                // store user
                $user = $this->storeUser($record, $userRepository);
                // store employee
                $this->employees->push($employeeRepository->updateOrCreate(['id' => $user->id], $record + ['id' => $user->id]));
                // store identifications
                $this->storeIdentificationCodes($user->id, $record['identifications'], $identificationRepository);
                // store work shifts
                $this->storeWorkShifts($user->id, $record['work_shifts'], $employeeRepository, $workShiftRepository);
            }

            // trash missing data from csv file
            $workShiftRepository->deleteWhereNotIn('id', $this->workShifts->pluck('id')->all());
            $costCenterRepository->deleteWhereNotIn('id', $this->costCenters->pluck('id')->all());
            // delete missing employees and their related users
            $syncedEmployeesIds = $this->employees->pluck('id')->all();
            $employeesToDelete = $employeeRepository->findWhereNotIn('id', $syncedEmployeesIds, ['id'])->pluck('id');
            $userRepository->deleteWhereIn('id', $employeesToDelete->all());
            $employeeRepository->deleteWhereNotIn('id', $syncedEmployeesIds);
        } catch (Exception $e) {
            $userRepository->find($this->userId)->notify(new FailedEmployeesSyncNotification($e->getMessage()));

            return false;
        }

        $userRepository->find($this->userId)->notify(new SuccessfulEmployeesSyncNotification(count($records)));

        return true;
    }

    /**
     * @param string                        $costCenter
     * @param CostCenterRepositoryInterface $costCenterRepository
     */
    private function storeCostCenter(string $costCenter, CostCenterRepositoryInterface $costCenterRepository): CostCenter
    {
        [$code, $name] = array_map('trim', explode(':', $costCenter));

        if ($costCenter = $this->costCenters->where('code', $code)->first()) {
            return $costCenter;
        }

        $costCenter = $costCenterRepository->updateOrCreate(
            ['code' => $code], ['code' => $code, 'name' => $name]
        );

        $this->costCenters->push($costCenter);

        return $costCenter;
    }

    /**
     * @param  array                   $user
     * @param  UserRepositoryInterface $userRepository
     * @return mixed
     */
    private function storeUser(array $user, UserRepositoryInterface $userRepository)
    {
        $password = Arr::only($user, ['code', 'identification_number', 'email']);
        $user['password'] = Hash::make(implode('@', $password));
        $userKeys = Arr::only($user, ['email']);

        return $userRepository->updateOrCreate($userKeys, $user);
    }

    /**
     * @param array                             $identificationCodes
     * @param IdentificationRepositoryInterface $identificationRepository
     */
    private function storeIdentificationCodes(
        int $userId, string $identificationCodes, IdentificationRepositoryInterface $identificationRepository
    ) {
        // delete old employee identification codes
        $identificationRepository->deleteWhere(['employee_id' => $userId]);
        // store newly employee identification codes
        $identificationCodes = explode(',', $identificationCodes);
        $identificationCodes = array_map('trim', $identificationCodes);

        $mapIdentifications = function ($identificationString) {
            [$identificationName, $identificationCode] = explode(':', $identificationString);

            return ['name' => $identificationName, 'code' => $identificationCode];
        };

        $identificationCodes = array_map($mapIdentifications, $identificationCodes);
        data_fill($identificationCodes, '*.employee_id', $userId);

        return $identificationRepository->insert($identificationCodes);
    }

    /**
     * @param  int                         $userId
     * @param  string                      $workShifts
     * @param  EmployeeRepositoryInterface $employeeRepository
     * @return mixed
     */
    private function storeWorkShifts(
        int $userId,
        string $workShifts,
        EmployeeRepositoryInterface $employeeRepository,
        WorkShiftRepositoryInterface $workShiftRepository
    ) {
        $workShifts = new Collection(array_map('trim', explode(',', $workShifts)));
        $workShifts = $workShifts->map(function ($workShift) use ($workShiftRepository) {
            $timeSlots = new Collection(explode('|', $workShift));
            $timeSlots = $timeSlots->map(function ($timeSlot) {
                [$start, $end] = explode('-', $timeSlot);

                return ['start' => $this->parseTime($start), 'end' => $this->parseTime($end)];
            });

            $name = $this->solveWorkShiftName($timeSlots);
            $workShift = $this->workShifts->where('name', $name)->first();

            if (! $workShift) {
                $workShift = $workShiftRepository->updateOrCreate(
                    ['name' => $name], ['name' => $name, 'time_slots' => $timeSlots->all()]
                );

                $this->workShifts->push($workShift);
            }

            return $workShift;
        });

        return $employeeRepository->sync($userId, 'workShifts', $workShifts->pluck('id'));
    }

    /**
     * Transform the given $time string to a qualified time, e.g.:
     * when $time is "6", returns "06:00"
     * when $time is "12:30", returns "12:30"
     * when $time is "14:", returns "14:00".
     *
     * @param string $time
     */
    private function parseTime(string $time): string
    {
        $time = array_filter(explode(':', $time));
        count($time) > 1 ? null : array_push($time, '00');
        $time[0] = str_pad($time[0], 2, '0', STR_PAD_LEFT);

        return implode(':', $time);
    }

    /**
     * @param Illuminate\Support\Collection $timeSlots
     */
    public function solveWorkShiftName(Collection $timeSlots): string
    {
        $start = $timeSlots->pluck('start')->first();
        $end = $timeSlots->pluck('end')->last();

        $start = Arr::first(explode(':', $start));
        $end = Arr::first(explode(':', $end));

        return "{$start}-{$end}";
    }
}
