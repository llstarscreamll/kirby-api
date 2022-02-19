<?php

namespace Kirby\Company\UI\CLI;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kirby\Company\Contracts\HolidayRepositoryInterface;
use Kirby\Company\Contracts\HolidaysServiceInterface;

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
    protected $signature = 'company:sync-holidays {--next-year : Sync next year holidays}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync holidays';

    /**
     * Create a new command instance.
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
        $countryCode = 'co';
        $currentDate = $this->option('next-year') ? now()->addYear() : now();
        $holidays = $holidaysService->get($countryCode, $currentDate->year);

        data_fill($holidays, '*.country_code', $countryCode);

        (new Collection($holidays))->each(function ($holiday) use ($holidayRepository) {
            $keys = Arr::only($holiday, ['country_code', 'date']);
            $holidayRepository->updateOrCreate($keys, $holiday);
        });

        return 0;
    }
}
