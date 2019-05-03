<?php

namespace llstarscreamll\Users\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Class SyncEmployeesByCsvFileJob.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SyncEmployeesByCsvFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var mixed
     */
    private $csvFilePath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $csvFilePath)
    {
        $this->csvFilePath = $csvFilePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
