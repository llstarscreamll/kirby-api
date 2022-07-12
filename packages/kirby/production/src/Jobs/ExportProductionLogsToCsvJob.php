<?php

namespace Kirby\Production\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kirby\Production\Exports\ProductionLogsExport;
use Kirby\Production\Notifications\ProductionLogsCsvReady;
use Kirby\Users\Models\User;
use Maatwebsite\Excel\Facades\Excel;

class ExportProductionLogsToCsvJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * 9.5 seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = (60 * 9) + 30;

    /**
     * @var \Kirby\Users\Models\User
     */
    public $user;

    /**
     * @var array
     */
    public $params;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $params)
    {
        $this->user = $user;
        $this->params = $params;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $now = now()->format('Y-m-d-h-i-s');
        $filePath = "production-logs/exports/{$now}-production-logs.csv";

        Excel::store(new ProductionLogsExport($this->params), $filePath, 'public');

        $this->user->notify(new ProductionLogsCsvReady($filePath));
    }
}
