<?php

namespace Kirby\TruckScale\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportWeighingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $userID;
    public array $filters = [];

    public function __construct(array $filters, int $userID)
    {
        $this->filters = $filters;
        $this->userID = $userID;
    }

    public function handle()
    {
    }
}
