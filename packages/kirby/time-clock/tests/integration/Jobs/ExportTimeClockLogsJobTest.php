<?php

namespace Kirby\TimeClock\Tests\integration\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Kirby\TimeClock\Jobs\ExportTimeClockLogsJob;
use Kirby\TimeClock\Models\TimeClockLog;
use Kirby\TimeClock\Notifications\TimeClockLogsExportFileNotification;
use Kirby\Users\Models\User;

/**
 * Class ExportTimeClockLogsJobTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class ExportTimeClockLogsJobTest extends \Tests\TestCase
{
    private string $testDate = '2022-06-24 10:10:10';
    private string $filePath = 'app/public/time-clock-logs/exports/timeClockLogs_2022-06-24_101010.csv';

    protected function tearDown(): void
    {
        @unlink(storage_path($this->filePath));

        parent::tearDown();
    }

    /** @test */
    public function shouldWriteCsvOnFileSystem()
    {
        Carbon::setTestNow($this->testDate);
        $logs = factory(TimeClockLog::class, 10)->create(['checked_in_at' => now()->subDays(15)]);

        $params = [
            'search' => 'employee_id:'.$logs->pluck('employee_id')->join(','),
            'checkedInStart' => now()->subMonth()->toIsoString(),
            'checkedInEnd' => now()->toIsoString(),
        ];

        ExportTimeClockLogsJob::dispatch(factory(User::class)->create()->id, $params);

        $this->assertFileExists(storage_path($this->filePath));
        $this->assertNotEmpty(file_get_contents(storage_path($this->filePath)));
    }

    /** @test */
    public function shouldDispatchNotificationWhenCsvIsGeneratedSuccesfuly()
    {
        Notification::fake();

        Carbon::setTestNow($this->testDate);
        factory(TimeClockLog::class, 10)->create();
        $user = factory(User::class)->create();

        ExportTimeClockLogsJob::dispatch($user->id, []);

        Notification::assertSentTo($user, TimeClockLogsExportFileNotification::class);
    }
}
