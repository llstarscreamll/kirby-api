<?php

namespace llstarscreamll\TimeClock\UI\CLI;

use Faker\Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use llstarscreamll\Users\Models\User;
use llstarscreamll\Employees\Models\Employee;
use llstarscreamll\WorkShifts\Models\WorkShift;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Novelties\Actions\RegisterTimeClockNoveltiesAction;

/**
 * Class GenerateFakeTimeClockDataCommand.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GenerateFakeTimeClockDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'time-clock:generate-fake-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate fake time clock data (employees, time clock logs and novelties)';

    /**
     * @var mixed
     */
    private $faker;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Generator $faker)
    {
        $this->faker = $faker;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $lastTimeClockLog = TimeClockLog::orderBy('id', 'desc')->first();
        $startFrom = optional($lastTimeClockLog)->checked_out_at ?? now()->subMonths(2);
        $days = $startFrom->diffInDays(now());
        $workShifts = WorkShift::pluck('id');
        $this->noveltyTypes = NoveltyType::all();
        $workShiftsGroups = [$workShifts->take(3), $workShifts->take(-2)];
        $registerNoveltiesAction = app(RegisterTimeClockNoveltiesAction::class);
        $existsEmployees = Employee::count() > 0;
        $employees = $existsEmployees
            ? Employee::all()
            : factory(Employee::class, 30)->create()
            ->map(function ($employee) use ($workShiftsGroups) {
                $employee->workShifts()->sync($this->faker->randomElement($workShiftsGroups));

                return $employee;
            });
        $this->clockers = User::inRandomOrder()->limit(10)->get()->random(5);

        $this->line("Proceed to create time clock data for {$employees->count()} employees, starting {$days} days ago");

        for ($i = $days; $i >= 0; $i--) {
            $daysAgo = $i;
            $employees->map(function ($employee) use ($registerNoveltiesAction, $daysAgo) {
                // create time clock logs for employee
                $timeClocks = $employee->timeClockLogs()->createMany($this->createTimeClogLogsForEmployee($employee, $daysAgo));

                $timeClocks->each(function ($timeClockLog) use ($registerNoveltiesAction) {
                    $registerNoveltiesAction->run($timeClockLog->id);
                });

                return $employee;
            });

            $this->line("day #$i processed");
        }
    }

    /**
     * @param $employee
     */
    private function createTimeClogLogsForEmployee(Employee $employee, int $daysAgo)
    {
        $timeClockLogs = new Collection();

        $timeClockLogs->push($this->createTimeClockLog($employee, $daysAgo));

        return $timeClockLogs->filter()->all();
    }

    /**
     * @param $employee
     */
    private function createTimeClockLog($employee, $daysAgo)
    {
        $date = now()->subDays($daysAgo);
        $workShift = $employee->workShifts->filter(function ($workShift) use ($date) {
            return in_array($date->dayOfWeekIso, $workShift->applies_on_days);
        })->first();

        if (! $workShift && $this->faker->boolean($chanceOfGettingTrue = 40)) {
            $noveltyType = $this->noveltyTypes->whereIn('code', ['HEDI', 'HADI'])->random();

            return [
                'employee_id' => $employee->id,
                'check_in_novelty_type_id' => $noveltyType->id,
                'checked_in_at' => $date->copy()->setTime(
                    $this->faker->numberBetween(7, 10), $this->faker->randomElement([10, 20, 30, 40])
                ),
                'checked_out_at' => $date->copy()->addHours($this->faker->numberBetween(3, 9)),
                'checked_in_by_id' => $this->clockers->random()->id,
                'checked_out_by_id' => $this->clockers->random()->id,
            ];
        }

        if (! $workShift) {
            return;
        }

        $timeSlot = $this->faker->randomElement($workShift->time_slots);
        $startNoveltyType = null;
        $endNoveltyType = null;
        [$hours, $minutes] = explode(':', $timeSlot['start']);
        $startTime = $date->copy()->setTime($hours, $minutes);
        $startTime->setMinutes($this->faker->numberBetween(-10, 12));
        [$hours, $minutes] = explode(':', $timeSlot['end']);
        $endTime = $date->copy()->setTime($hours, $minutes);
        $endTime->setMinutes($this->faker->numberBetween(-10, 12));

        // apply start time punctuality variation?
        if ($foo = $this->faker->boolean($chanceOfGettingTrue = 50)) {
            $hoursVariation = $this->faker->numberBetween(-2, 2);
            $startTime->addHours($hoursVariation);
            $startNoveltyType = $hoursVariation > 0
                ? $this->noveltyTypes->whereIn('code', ['PP'])->random()
                : ($hoursVariation < 0 ? $this->noveltyTypes->whereIn('code', ['HADI'])->random() : null);
        }

        // apply end time punctuality variation?
        if ($this->faker->boolean($chanceOfGettingTrue = 50)) {
            $hoursVariation = $this->faker->numberBetween(-2, 2);
            $endTime->addHours($hoursVariation);
            $endNoveltyType = $hoursVariation > 0
                ? $this->noveltyTypes->whereIn('code', ['HADI'])->random()
                : ($hoursVariation < 0 ? $this->noveltyTypes->whereIn('code', ['PP'])->random() : null);
        }

        return [
            'employee_id' => $employee->id,
            'work_shift_id' => $workShift->id,
            'checked_in_at' => $startTime,
            'check_in_novelty_type_id' => optional($startNoveltyType)->id,
            'checked_out_at' => $endTime,
            'check_out_novelty_type_id' => optional($endNoveltyType)->id,
            'checked_in_by_id' => $this->clockers->random()->id,
            'checked_out_by_id' => $this->clockers->random()->id,
        ];
    }
}
