<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Kirby\Products\Contracts\CategoryRepository;
use Kirby\Products\Contracts\ProductRepository;
use Kirby\Products\Models\Category;
use League\Csv\Reader;

class SyncProductsByCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync-by-csv {csv-path : path to csv file} {--images-path= : path from product images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync product by csv file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ProductRepository $productRepo)
    {
        $csvPath = $this->argument('csv-path');
        $reader = Reader::createFromPath($csvPath);
        $imagesPath = $this->option('images-path');
        $reader->setDelimiter(';')->setHeaderOffset(0);

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($reader as $row) {
            $row['slug'] = Str::slug($row['name']);

            /**
             * @var \Kirby\Products\Models\Product
             */
            $product = $productRepo->firstOrNew(Arr::only($row, ['code']));

            if (! $product->exists && empty($imagesPath)) {
                $row['sm_image_url'] = config('shop.default-product-image');
                $row['md_image_url'] = config('shop.default-product-image');
                $row['lg_image_url'] = config('shop.default-product-image');
            }

            if ($product->exists) {
                $productRepo->update(Arr::except($row, ['categories']), $product->id);
                $updatedCount++;
            }

            if (! $product->exists) {
                $product = $productRepo->create(Arr::except($row, ['categories']));
                $createdCount++;
            }

            $categories = collect(explode('|', $row['categories']))
                ->map(fn ($categoryName) => $this->writeCategory($categoryName, Str::slug($categoryName)));

            $product->categories()->sync($categories->pluck('id'));
        }

        $this->info("{$createdCount} products created successfully");
        $this->info("{$updatedCount} products updated successfully");
    }

    private function writeCategory(string $name, string $slug): Category
    {
        /**
         * @var \Kirby\Products\Contracts\CategoryRepository
         */
        $categoryRepo = app(CategoryRepository::class);
        $category = $categoryRepo->firstOrNew(['slug' => $slug]);

        if ($category->exists) {
            return $category;
        }

        return $categoryRepo->create([
            'name' => $name,
            'slug' => $slug,
            'position' => 9999,
            'active' => true,
        ]);
    }
}
