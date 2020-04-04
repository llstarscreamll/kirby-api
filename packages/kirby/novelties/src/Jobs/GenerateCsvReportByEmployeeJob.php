<?php

namespace Kirby\Novelties\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Kirby\Novelties\Exports\NoveltiesExport;
use Kirby\Users\Contracts\UserRepositoryInterface;
use Kirby\Novelties\Notifications\NoveltiesExportReady;

/**
 * Class GenerateCsvReportByEmployeeJob.
 * 
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GenerateCsvReportByEmployeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
     *
     * @param  int    $userId
     * @param  array  $params
     * @return void
     */
    public function __construct(int $userId, array $params)
    {
        $this->userId = $userId;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UserRepositoryInterface $userRepository)
    {
        $startDate = Carbon::parse($this->params['start_date'])->format('Y-m-d_H-i-s');
        $endDate = Carbon::parse($this->params['end_date'])->format('Y-m-d_H-i-s');
        $filePath = "novelties/exports/novelties_{$startDate}_{$endDate}.csv";

        Excel::store(new NoveltiesExport($this->params), $filePath, 'public');

        $user = $userRepository->find($this->userId);
        $user->notify(new NoveltiesExportReady($filePath));

        return true;
    }
}
