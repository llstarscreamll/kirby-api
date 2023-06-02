<?php

namespace Kirby\TruckScale\Tests\Api\V1\Weighings;

use Illuminate\Support\Facades\Queue;
use Kirby\TruckScale\Jobs\ExportWeighingsJob;
use Kirby\Users\Models\User;
use Tests\TestCase;

/**
 * @internal
 */
class ExportWeighingsTest extends TestCase
{
    private $method = 'POST';
    private $path = 'api/1.0/weighings/export';

    /** @test */
    public function shouldInvokeQueueJobForDataExportGeneration()
    {
        $this->seed(\TruckScalePackageSeeder::class);

        Queue::fake();

        $this->actingAsAdmin($user = factory(User::class)->create())
            ->json($this->method, "{$this->path}?filter[id]=123&filter[vehicle_plate]=AAA111&filter[vehicle_type]=one&filter[status]=finished&filter[date]=2023-01-01")
            ->assertOk();

        Queue::assertPushed(
            ExportWeighingsJob::class,
            fn ($job) => $job->userID === $user->id
            && 123 == $job->filters['id']
            && 'AAA111' === $job->filters['vehicle_plate']
            && 'one' === $job->filters['vehicle_type']
            && 'finished' === $job->filters['status']
            && '2023-01-01' === $job->filters['date']
        );
    }
}
