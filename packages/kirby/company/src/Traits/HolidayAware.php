<?php

namespace Kirby\Company\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Kirby\Company\Contracts\HolidayRepositoryInterface;

trait HolidayAware
{
    /**
     * @var \Kirby\Company\Contracts\HolidayRepositoryInterface
     */
    private $holidayRepository;

    /**
     * Check if $date is holiday.
     */
    public function isHoliday(Carbon $date): bool
    {
        $holidaysCount = $this->holidayRepository()->countWhereIn('date', [$date->toDateString()]);

        return $holidaysCount || $date->isSunday();
    }

    /**
     * Check if any of the $dates are holidays.
     */
    public function hasAnyHoliday(array $dates): bool
    {
        $areHolidays = array_map(fn (Carbon $date) => $this->isHoliday($date), $dates);

        return count(array_filter($areHolidays)) > 0;
    }

    private function holidayRepository(): HolidayRepositoryInterface
    {
        if (! $this->holidayRepository) {
            $this->holidayRepository = App::make(HolidayRepositoryInterface::class);
        }

        return $this->holidayRepository;
    }
}
