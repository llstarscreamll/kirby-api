<?php

namespace Kirby\Novelties\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kirby\Novelties\Exports\NoveltiesExport;
use Kirby\Novelties\Notifications\NoveltiesExportReady;
use Kirby\Users\Contracts\UserRepositoryInterface;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class GenerateCsvReportByEmployeeJob.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GenerateCsvReportByEmployeeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var int
     */
    public $userId;

    /**
     * @var array
     */
    public $params;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, array $params)
    {
        $this->userId = $userId;
        $this->params = $params;
    }

    /**
     * Execute the job.
     */
    public function handle(UserRepositoryInterface $userRepository)
    {
        $filePath = sprintf('novelties/exports/novelties_%s.csv', str_replace([' ', ':'], ['_', ''], now()->toDateTimeString()));

        Excel::store(new NoveltiesExport($this->params), $filePath, 'public');

        $user = $userRepository->find($this->userId);
        $user->notify(new NoveltiesExportReady($filePath));

        return true;
    }
}
