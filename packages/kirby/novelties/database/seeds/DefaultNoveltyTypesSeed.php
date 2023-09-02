<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\NoveltyType;

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
                ['start' => '06:00:00', 'end' => '21:00:00'],
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
                ['start' => '21:00:01', 'end' => '05:59:59'],
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
                ['start' => '06:00:00', 'end' => '21:00:00'],
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
                ['start' => '21:00:01', 'end' => '05:59:59'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HDEF',
            'name' => 'Hora Diurna Extra Festiva',
            'context_type' => null,
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Holiday,
            'apply_on_time_slots' => [
                ['start' => '06:00:00', 'end' => '21:00:00'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HENF',
            'name' => 'Hora Nocturna Extra Festiva',
            'context_type' => null,
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Holiday,
            'apply_on_time_slots' => [
                ['start' => '21:00:01', 'end' => '05:59:59'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HEDI',
            'name' => 'Hora Extra Diurna',
            'context_type' => null,
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Workday,
            'apply_on_time_slots' => [
                ['start' => '06:00:00', 'end' => '21:00:00'],
            ],
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'HENO',
            'name' => 'Hora Nocturna Extra',
            'context_type' => null,
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => DayType::Workday,
            'apply_on_time_slots' => [
                ['start' => '21:00:01', 'end' => '05:59:59'],
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
        [
            'code' => 'B+',
            'name' => 'Balance Positivo',
            'context_type' => null,
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => null, // any day
            'apply_on_time_slots' => null, // any time
            'operator' => NoveltyTypeOperator::Addition,
        ],
        [
            'code' => 'B-',
            'name' => 'Balance Negativo',
            'context_type' => null,
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => null, // any day
            'apply_on_time_slots' => null, // any time
            'operator' => NoveltyTypeOperator::Subtraction,
        ],
        [
            'code' => 'PPNR',
            'name' => 'Permiso personal NO remunerado',
            'context_type' => null,
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => null, // any day
            'apply_on_time_slots' => null, // any time
            'operator' => NoveltyTypeOperator::Subtraction,
        ],
        [
            'code' => 'PPR',
            'name' => 'Permiso personal remunerado',
            'context_type' => null,
            'time_zone' => 'America/Bogota',
            'apply_on_days_of_type' => null, // any day
            'apply_on_time_slots' => null, // any time
            'operator' => NoveltyTypeOperator::Addition,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (NoveltyType::count() > count($this->noveltyTypes)) {
            return;
        }
        collect($this->noveltyTypes)->map(function (array $noveltyType) {
            $keys = Arr::only($noveltyType, ['code']);

            return NoveltyType::updateOrCreate($keys, $noveltyType);
        });
    }
}
