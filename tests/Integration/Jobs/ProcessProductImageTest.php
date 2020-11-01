<?php

namespace Tests\Integration\Jobs;

use Tests\TestCase;
use App\Jobs\ProcessProductImage;
use App\Events\ProductImageProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

class SearchProductImageTest extends TestCase
{
    protected function tearDown(): void
    {
        @unlink(storage_path("app/images/products/bar/baz/C3-D4.jpg"));
        @unlink(storage_path("app/public/images/products/sm/C3-D4.jpg"));
        @unlink(storage_path("app/public/images/products/md/C3-D4.jpg"));
        @unlink(storage_path("app/public/images/products/lg/C3-D4.jpg"));

        @unlink("/tmp/sm_C3-D4.jpg");
        @unlink("/tmp/md_C3-D4.jpg");
        @unlink("/tmp/lg_C3-D4.jpg");
        parent::tearDown();
    }

    /**
     * @test
     */
    public function shouldCreateThreeScaledImagesWhenImageExists()
    {
        @mkdir(storage_path("app/images/products/bar/baz"), 0777, true);
        @file_put_contents(
            storage_path("app/images/products/bar/baz/C3-D4.jpg"),
            file_get_contents(base_path('tests/_data/images/test_image.jpg'))
        );

        Event::fake();
        Storage::fake();

        $job = new ProcessProductImage('bar/baz/C3-D4.jpg');
        $result = $job->handle();

        $this->assertTrue($result);
        Storage::disk('public-local-images')->assertExists('sm/C3-D4.jpg');
        Storage::disk('public-local-images')->assertExists('md/C3-D4.jpg');
        Storage::disk('public-local-images')->assertExists('lg/C3-D4.jpg');

        Storage::disk('private-local-images')->assertMissing('bar/baz/C3-D4.jpg');

        Event::assertDispatched(ProductImageProcessed::class, function ($event) {return $event->productCode === 'C3-D4';});
    }

    /**
     * @test
     */
    public function shouldReturnFalseIfFileDoesNotExists()
    {
        $job = new ProcessProductImage('fake/path/to-image.jpg');
        $this->assertFalse($job->handle());
    }
}
