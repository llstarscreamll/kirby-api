<?php

namespace Kirby\Novelties\Tests\api\NoveltyTypes;

use Kirby\Novelties\Models\NoveltyType;
use NoveltiesPackageSeed;

/**
 * Class UpdateNoveltyTypeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class UpdateNoveltyTypeTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelty-types/{id}';

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function getSuccessfully()
    {
        $noveltyTypeId = factory(NoveltyType::class)->create()->id;
        $expectedData = factory(NoveltyType::class)->make([
            'apply_on_time_slots' => [
                ['start' => '08:00', 'end' => '12:00'],
            ],
            'time_zone' => 'America/Bogota',
        ]);

        $endpoint = str_replace('{id}', $noveltyTypeId, $this->endpoint);
        $this->json('PUT', $endpoint, $expectedData->toArray())
            ->assertOk()
            ->assertJsonPath('data.id', $noveltyTypeId);

        $this->assertDatabaseHas('novelty_types', [
            'id' => $noveltyTypeId,
            'code' => $expectedData['code'],
            'name' => $expectedData['name'],
            'context_type' => $expectedData['context_type'],
            'time_zone' => $expectedData['time_zone'],
            'apply_on_days_of_type' => $expectedData['apply_on_days_of_type'],
            'apply_on_time_slots' => $this->castAsJson($expectedData['apply_on_time_slots']),
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
        factory(NoveltyType::class)->create();
        factory(NoveltyType::class)->create();
        factory(NoveltyType::class)->create();

        $noveltyTypeId = factory(NoveltyType::class)->create()->id;
        $expectedData = factory(NoveltyType::class)->make([
            'code' => 'foo', // code taken from first novelty type
            'apply_on_time_slots' => [
                ['start' => '08:00', 'end' => '12:00'],
            ],
            'time_zone' => 'America/Bogota',
        ]);

        $endpoint = str_replace('{id}', $noveltyTypeId, $this->endpoint);
        $this->json('PUT', $endpoint, $expectedData->toArray())->assertStatus(422);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $expectedData = factory(NoveltyType::class)->make();

        $endpoint = str_replace('{id}', 1, $this->endpoint);
        $this->actingAsGuest()->json('PUT', $endpoint, $expectedData->toArray())->assertForbidden();
    }
}
