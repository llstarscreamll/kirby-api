<?php

namespace Kirby\TimeClock\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kirby\TimeClock\Exports\TimeClockLogsExport;
use Kirby\TimeClock\Notifications\TimeClockLogsExportFileNotification;
use Kirby\Users\Models\User;
use Maatwebsite\Excel\Facades\Excel;

class ExportTimeClockLogsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array */
    public $params;

    /** @var int */
    public $userID;

    public function __construct(int $userID, array $params)
    {
        $this->params = $params;
        $this->userID = $userID;
    }

    public function handle()
    {
        $filePath = sprintf(
            'time-clock-logs/exports/timeClockLogs_%s.csv',
            now()->format('Y-m-d_His')
        );

        Excel::store(new TimeClockLogsExport($this->params), $filePath, 'public');

        User::find($this->userID)->notify(new TimeClockLogsExportFileNotification($filePath));
    }
}
