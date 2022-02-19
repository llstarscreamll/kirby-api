<?php

namespace Kirby\Novelties\Tests\api\NoveltyTypes;

use Kirby\Novelties\Models\NoveltyType;
use NoveltiesPackageSeed;

/**
 * Class CreateNoveltyTypeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class CreateNoveltyTypeTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelty-types/';

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function createSuccessfully()
    {
        $expectedData = factory(NoveltyType::class)->make([
            'apply_on_time_slots' => [
                ['start' => '08:00', 'end' => '12:00'],
            ],
            'time_zone' => 'America/Bogota',
        ]);

        $this->json('POST', $this->endpoint, $expectedData->toArray())
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('novelty_types', [
            'code' => $expectedData['code'],
            'name' => $expectedData['name'],
            'context_type' => $expectedData['context_type'],
            'time_zone' => $expectedData['time_zone'],
            'apply_on_days_of_type' => $expectedData['apply_on_days_of_type'],
            'apply_on_time_slots' => json_encode($expectedData['apply_on_time_slots']),
            'operator' => $expectedData['operator'],
            'requires_comment' => $expectedData['requires_comment'],
            'keep_in_report' => $expectedData['keep_in_report'],
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnUnprocesableEntityWhenCodeIsAlreadyTaken()
    {
        factory(NoveltyType::class)->create(['code' => 'foo']);
        $requestPayload = factory(NoveltyType::class)->make(['code' => 'foo']);

        $this->json('POST', $this->endpoint, $requestPayload->toArray())
            ->assertStatus(422);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $expectedData = factory(NoveltyType::class)->make();

        $this->json('POST', $this->endpoint, $expectedData->toArray())
            ->assertForbidden();
    }
}
