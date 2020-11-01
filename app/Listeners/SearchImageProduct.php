<?php

namespace App\Listeners;

use App\Events\ProductImageProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Kirby\Products\Contracts\ProductRepository;

class SearchImageProduct implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ProductImageProcessed $event
     * @return void
     */
    public function handle(ProductImageProcessed $event)
    {
        $smallImage = null;
        $mediumImage = null;
        $largeImage = null;

        if (Storage::disk(config('filesystems.public-images-disk'))->exists("sm/{$event->productCode}.jpg")) {
            $smallImage = Storage::disk(config('filesystems.public-images-disk'))->url("sm/{$event->productCode}.jpg");
        }

        if (Storage::disk(config('filesystems.public-images-disk'))->exists("md/{$event->productCode}.jpg")) {
            $mediumImage = Storage::disk(config('filesystems.public-images-disk'))->url("md/{$event->productCode}.jpg");
        }

        if (Storage::disk(config('filesystems.public-images-disk'))->exists("lg/{$event->productCode}.jpg")) {
            $largeImage = Storage::disk(config('filesystems.public-images-disk'))->url("lg/{$event->productCode}.jpg");
        }

        $productImages = ['sm_image_url' => $smallImage, 'md_image_url' => $mediumImage, 'lg_image_url' => $largeImage];

        app(ProductRepository::class)->updateByCode(array_filter($productImages), $event->productCode);
    }
}
