<?php

use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Kirby\Products\Models\Category;

/**
 * Class DefaultCategoriesSeed.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DefaultCategoriesSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        $categories = [
            ['name' => 'Laptops', 'slug' => 'laptops', 'position' => 4, 'active' => true],
            ['name' => 'Cellphones', 'slug' => 'cellphones', 'position' => 3, 'active' => true],
            ['name' => 'Computers & Tablets', 'slug' => 'computers-tablets', 'position' => 2, 'active' => true],
            ['name' => 'Video Games', 'slug' => 'video-games', 'position' => 1, 'active' => false],
        ];

        array_map(fn($category) => Category::updateOrCreate(Arr::only($category, ['slug']), $category), $categories);
    }
}
