<?php

namespace Tests\Feature\Commands;

use App\Jobs\ProcessProductImage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Class ProcessProductsImagesTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ProcessProductsImagesTest extends TestCase
{
    use WithFaker;

    protected function tearDown(): void
    {
        @unlink(storage_path('app/images/products/foo/A1-B2.jpg'));
        @unlink(storage_path('app/images/products/bar/baz/C3-D4.png'));
        @unlink(storage_path('app/images/products/E5-C6.jpeg'));
        @rmdir(storage_path('app/images/products'));

        parent::tearDown();
    }

    /**
     * @test
     */
    public function shouldDispatchJobsByEachImagePresentOnStorage()
    {
        @mkdir(storage_path('app/images/products/foo'), 0777, true);
        @mkdir(storage_path('app/images/products/bar/baz'), 0777, true);
        @touch(storage_path('app/images/products/foo/A1-B2.jpg'));
        @touch(storage_path('app/images/products/bar/baz/C3-D4.png'));
        @touch(storage_path('app/images/products/E5-C6.jpeg'));

        Queue::fake();

        $this->artisan('products:process-images')
            ->assertExitCode(0)
            ->expectsOutput('3 images scheduled for processing');

        Queue::assertPushed(ProcessProductImage::class, fn ($job) => $job->imagePath === 'foo/A1-B2.jpg');
        Queue::assertPushed(ProcessProductImage::class, fn ($job) => $job->imagePath === 'bar/baz/C3-D4.png');
        Queue::assertPushed(ProcessProductImage::class, fn ($job) => $job->imagePath === 'E5-C6.jpeg');
    }
}
