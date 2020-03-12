<?php

use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\Novelties\Enums\NoveltyTypeOperator;

/**
 * Class DefaultNoveltyTypesSeed.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DefaultNoveltyTypesSeed extends Seeder
{
    /**
     * @var array
     */
    private $noveltyTypes = [
        [
            'code' => 'PP',
            'name' => 'Permiso Personal',
            'context_type' => 'elegible_by_user',
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => null, // any day
            'apply_on_time_slots' => null, // any time
            'operator' => NoveltyTypeOperator::Subtraction,
        ],
        [
            'code' => 'CM',
            'name' => 'Cita médica',
            'context_type' => null,
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => null, // any day
            'apply_on_time_slots' => null, // any time
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HN',
            'name' => 'Hora normal',
            'context_type' => 'normal_work_shift_time',
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Workday,
            'apply_on_time_slots' => [
                ['start' => '06:00', 'end' => '21:00'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'RECNO',
            'name' => 'Recargo Nocturno',
            'context_type' => 'normal_work_shift_time',
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Workday,
            'apply_on_time_slots' => [
                ['start' => '21:00', 'end' => '06:00'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HDF',
            'name' => 'Hora Diurna Festiva',
            'context_type' => 'normal_work_shift_time',
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Holiday,
            'apply_on_time_slots' => [
                ['start' => '06:00', 'end' => '21:00'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HNF',
            'name' => 'Hora Nocturna Festiva',
            'context_type' => 'normal_work_shift_time',
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Holiday,
            'apply_on_time_slots' => [
                ['start' => '21:00', 'end' => '06:00'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HDEF',
            'name' => 'Hora Diurna Extra Festiva',
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Holiday,
            'apply_on_time_slots' => [
                ['start' => '06:00', 'end' => '21:00'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HENF',
            'name' => 'Hora Nocturna Extra Festiva',
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Holiday,
            'apply_on_time_slots' => [
                ['start' => '21:00', 'end' => '06:00'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HEDI',
            'name' => 'Hora Extra Diurna',
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Workday,
            'apply_on_time_slots' => [
                ['start' => '06:00', 'end' => '21:00'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HENO',
            'name' => 'Hora Nocturna Extra',
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Workday,
            'apply_on_time_slots' => [
                ['start' => '21:00', 'end' => '06:00'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HADI',
            'name' => 'Hora Adicional',
            'context_type' => 'elegible_by_user',
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => null, // any day
            'apply_on_time_slots' => null, // any time
            'operator' => NoveltyTypeOperator::Addition,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        collect($this->noveltyTypes)->map(function (array $noveltyType) {
            $keys = Arr::only($noveltyType, ['code']);

            return NoveltyType::updateOrCreate($keys, $noveltyType);
        });
    }
}
