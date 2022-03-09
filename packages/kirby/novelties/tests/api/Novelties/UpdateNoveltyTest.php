<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;
use NoveltiesPackageSeed;

/**
 * Class UpdateNoveltyTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class UpdateNoveltyTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/{id}';

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function updateNoveltySuccessfully()
    {
        $novelty = factory(Novelty::class)->create();

        $startDate = now()->addDay();
        $endDate = now()->addDay()->addHours(2);

        $updatedNovelty = [
            'employee_id' => factory(Employee::class)->create()->id,
            'novelty_type_id' => factory(NoveltyType::class)->create(['operator' => NoveltyTypeOperator::Subtraction])->id,
            'start_at' => $startDate->toIsoString(),
            'end_at' => $endDate->toIsoString(),
            'comment' => 'updated comment here!!',
        ];

        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);
        $this->json('PUT', $endpoint, $updatedNovelty)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('novelties', [
            'id' => $novelty->id,
            'start_at' => $startDate->toDateTimeString(),
            'end_at' => $endDate->toDateTimeString(),
        ] + $updatedNovelty);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $endpoint = str_replace('{id}', 1, $this->endpoint);

        $this->actingAsGuest()
            ->json('PUT', $endpoint, [])
            ->assertForbidden();
    }
}
