<?php
namespace llstarscreamll\Company\UI\CLI;

use Illuminate\Console\Command;
use llstarscreamll\Company\Contracts\HolidaysServiceInterface;
use llstarscreamll\Company\Contracts\HolidayRepositoryInterface;

/**
 * Class SyncHolidaysCommand.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SyncHolidaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:sync-holidays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync holidays';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(
        HolidaysServiceInterface $holidaysService,
        HolidayRepositoryInterface $holidayRepository
    ) {
        $year = now()->year;
        $countryCode = 'co';
        $holidays = $holidaysService->get($countryCode, $year);

        data_fill($holidays, '*.country_code', $countryCode);
        $holidayRepository->insert($holidays);
    }
}
