<?php

namespace Novelties\Exports;

use Kirby\Company\Models\SubCostCenter;
use Kirby\Novelties\Exports\NoveltiesExport;
use Kirby\Novelties\Models\Novelty;
use Kirby\Users\Models\User;
use Novelties\IntegrationTester;

/**
 * Class NoveltiesExportCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesExportCest
{
    /**
     * @param IntegrationTester $I
     */
    public function _before(IntegrationTester $I)
    {
    }

    /**
     * @test
     * @param IntegrationTester $I
     */
    public function shouldHaveCertainHeadings(IntegrationTester $I)
    {
        $novelty = factory(Novelty::class)->create();

        $params = $params = [
            'employee_id' => $novelty->employee_id,
            'start_date' => now()->startOfMonth()->toDateTimeString(),
            'end_date' => now()->endOfMonth()->toDateTimeString(),
        ];

        $export = new NoveltiesExport($params);
        $result = $export->headings();

        $expected = [
            'Código empleado',
            'Identificación empelado',
            'Nombres empleado',
            'Apellidos empleado',
            'Fecha inicio',
            'Fecha fin',
            'Código centro de costo',
            'Código sub-centro de costo',
            'Código tipo de novedad',
            'Tiempo en horas',
            'Comentario',
            'Aprobadores',
            'Fecha de creación',
        ];

        $I->assertEquals($expected, $result);
    }

    /**
     * @test
     * @param IntegrationTester $I
     */
    public function shouldHaveCertainDataMap(IntegrationTester $I)
    {
        $novelty = factory(Novelty::class)->create([
            'sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
            'start_at' => now()->startOfMonth(),
            'end_at' => now()->startOfMonth()->addHours(2),
        ]);
        $approvers = factory(User::class, 1)->create(['first_name' => 'Tony', 'last_name' => 'Stark']);
        $approvers = $approvers->push(factory(User::class, 1)->create(['first_name' => 'Steve', 'last_name' => 'Rogers']));
        $novelty->approvals()->sync($approvers);

        $params = $params = [
            'employee_id' => $novelty->employee_id,
            'start_date' => now()->startOfMonth()->toDateTimeString(),
            'end_date' => now()->endOfMonth()->toDateTimeString(),
        ];

        $export = new NoveltiesExport($params);
        $result = $export->map($novelty);

        $expected = [
            $novelty->employee->code,
            $novelty->employee->identification_number,
            $novelty->employee->user->first_name,
            $novelty->employee->user->last_name,
            $novelty->start_at->toISOString(),
            $novelty->end_at->toISOString(),
            $novelty->subCostCenter->costCenter->code,
            $novelty->subCostCenter->code,
            $novelty->noveltyType->code,
            $novelty->total_time_in_hours,
            $novelty->comment,
            $novelty->approvals->map->name->join("\n"),
            $novelty->created_at->toISOString(),
        ];
        $I->assertEquals($expected, $result);
    }
}
