<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Kirby\Products\Contracts\ProductRepository;

class SearchProductImage implements ShouldQueue
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
     * @param  ProductCreated $event
     * @return void
     */
    public function handle(ProductCreated $event)
    {
        $smallImage = null;
        $mediumImage = null;
        $largeImage = null;

        if (Storage::disk(config('filesystems.public-images-disk'))->exists("sm/{$event->product->code}.jpg")) {
            $smallImage = Storage::disk(config('filesystems.public-images-disk'))->url("sm/{$event->product->code}.jpg");
        }

        if (Storage::disk(config('filesystems.public-images-disk'))->exists("md/{$event->product->code}.jpg")) {
            $mediumImage = Storage::disk(config('filesystems.public-images-disk'))->url("md/{$event->product->code}.jpg");
        }

        if (Storage::disk(config('filesystems.public-images-disk'))->exists("lg/{$event->product->code}.jpg")) {
            $largeImage = Storage::disk(config('filesystems.public-images-disk'))->url("lg/{$event->product->code}.jpg");
        }

        $productImages = ['sm_image_url' => $smallImage, 'md_image_url' => $mediumImage, 'lg_image_url' => $largeImage];

        app(ProductRepository::class)->update(array_filter($productImages), $event->product->id);
    }
}
