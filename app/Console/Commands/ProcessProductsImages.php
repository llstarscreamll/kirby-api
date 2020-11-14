<?php

namespace App\Console\Commands;

use App\Jobs\ProcessProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessProductsImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:process-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process products images present on private images disk';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $images = collect(Storage::disk(config('filesystems.private-images-disk'))->allFiles())
            ->filter(fn ($image) => Str::endsWith($image, ['.jpg', '.png', '.jpeg', '.webp']))
            ->map(fn ($image) => ProcessProductImage::dispatch($image));

        $this->info("{$images->count()} images scheduled for processing");
    }
}
