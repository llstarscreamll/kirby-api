<?php

namespace llstarscreamll\Employees\Jobs;

use Exception;
use League\Csv\Reader;
use League\Csv\Statement;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use llstarscreamll\Users\Contracts\UserRepositoryInterface;
use llstarscreamll\Employees\Contracts\EmployeeRepositoryInterface;
use llstarscreamll\WorkShifts\Contracts\WorkShiftRepositoryInterface;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\Employees\Notifications\FailedEmployeesSyncNotification;
use llstarscreamll\Employees\Notifications\SuccessfulEmployeesSyncNotification;

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
        'cost_center_id',
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
     * Create a new job instance.
     *
     * @param int    $userId
     * @param string $csvFilePath
     */
    public function __construct(int $userId, string $csvFilePath)
    {
        $this->userId = $userId;
        $this->csvFilePath = $csvFilePath;
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
        IdentificationRepositoryInterface $identificationRepository
    ) {
        $reader = Reader::createFromPath(storage_path("app/{$this->csvFilePath}"), 'r')->setDelimiter(';');
        $records = (new Statement())
            ->offset(1)
            ->process($reader, $this->fileColumns);

        try {
            foreach ($records as $row => $record) {
                // store user
                $user = $this->storeUser($record, $userRepository);
                // store employee
                $employee = $employeeRepository->updateOrCreate(['id' => $user->id], $record + ['id' => $user->id]);
                // store identifications
                $this->storeIdentificationCodes($user->id, $record['identifications'], $identificationRepository);
                // store work shifts
                $this->storeWorkShifts($user->id, $record['work_shifts'], $employeeRepository, $workShiftRepository);
            }
        } catch (Exception $e) {
            logger("Error sincronizando empleados: ", [$e->getMessage()]);
            $userRepository->find($this->userId)->notify(new FailedEmployeesSyncNotification($e->getMessage()));

            return false;
        }

        $userRepository->find($this->userId)->notify(new SuccessfulEmployeesSyncNotification(count($records)));

        return true;
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
        $workShiftNames = explode(',', $workShifts);
        $workShifts = $workShiftRepository->findWhereIn('name', $workShiftNames, ['id']);

        return $employeeRepository->sync($userId, 'workShifts', $workShifts);
    }
}
