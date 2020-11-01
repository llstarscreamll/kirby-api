<?php

namespace App\Jobs;

use App\Events\ProductImageProcessed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;

class ProcessProductImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    public $imagePath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $imagePath)
    {
        $this->imagePath = $imagePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! Storage::disk(config('filesystems.private-images-disk'))->exists($this->imagePath)) {
            return false;
        }

        $imageName = Str::afterLast($this->imagePath, '/');
        file_put_contents("/tmp/{$imageName}", Storage::disk(config('filesystems.private-images-disk'))->get($this->imagePath));

        $code = Str::before($imageName, '.');
        $image = new Imagick();
        $image->readImage("/tmp/{$imageName}");
        $image->setImageFormat('jpg');
        $image->setImageBackgroundColor('white');
        $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $image->extentImage(800, 800, ((800 - $image->getImageWidth()) / 2) * -1, ((800 - $image->getImageHeight()) / 2) * -1);

        $size = 800;
        $image->adaptiveResizeImage($size, $size, true);
        $image->writeImage("/tmp/lg_{$code}.jpg");

        $size = 400;
        $image->adaptiveResizeImage($size, $size, true);
        $image->writeImage("/tmp/md_{$code}.jpg");

        $size = 150;
        $image->adaptiveResizeImage($size, $size, true);
        $image->writeImage("/tmp/sm_{$code}.jpg");

        Storage::disk(config('filesystems.public-images-disk'))->putFileAs('lg', new File("/tmp/lg_{$code}.jpg"), "{$code}.jpg");
        Storage::disk(config('filesystems.public-images-disk'))->putFileAs('md', new File("/tmp/md_{$code}.jpg"), "{$code}.jpg");
        Storage::disk(config('filesystems.public-images-disk'))->putFileAs('sm', new File("/tmp/sm_{$code}.jpg"), "{$code}.jpg");

        Storage::disk(config('filesystems.private-images-disk'))->delete($this->imagePath);

        event(new ProductImageProcessed($code));

        return true;
    }
}
