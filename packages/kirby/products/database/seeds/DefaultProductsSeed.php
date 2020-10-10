<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Kirby\Products\Models\Category;
use Kirby\Products\Models\Product;

/**
 * Class DefaultProductsSeed.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DefaultProductsSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $products = [
            [
                'name' => 'MacBook Pro Intel Core I7 16" Pulgadas RAM 16 GB Disco Sólido 512 GB Gris espacial',
                'code' => '190199368217',
                'slug' => 'macbook-pro-intel-core-i7-16-pulgadas-ram-16-gb-disco-solido-512-gb-gris',
                'sm_image_url' => 'http://localhost:8000/images/products/1.png',
                'md_image_url' => 'http://localhost:8000/images/products/1.png',
                'lg_image_url' => 'http://localhost:8000/images/products/1.png',
                'cost' => 9000000,
                'price' => 10629000,
                'unity' => 'UND',
                'quantity' => '1',
                'pum_unity' => 'UND',
                'pum_price' => 10629000,
                'active' => true,
            ],
            [
                'name' => 'Portátil Gamer ROG Strix Scar III G531GW-AZ080T Intel Core i7 16GB RAM Disco Híbrido 1TB + 512GB SSD 15,6" Pulgadas Negro',
                'code' => '4718017354059',
                'slug' => 'portatil-gamer-asus-rog-strix-g531gw-intel-core-i7-15-6-pulgadas-disco-hibrido-1tb-y-512gb-ssd-negro',
                'sm_image_url' => 'http://localhost:8000/images/products/2.png',
                'md_image_url' => 'http://localhost:8000/images/products/2.png',
                'lg_image_url' => 'http://localhost:8000/images/products/2.png',
                'cost' => 8000000,
                'price' => 9749000,
                'unity' => 'UND',
                'quantity' => '1',
                'pum_unity' => 'UND',
                'pum_price' => 9749000,
                'active' => true,
            ],
            [
                'name' => 'Celular SAMSUNG Galaxy Note 10 Plus DS 256 GB Plateado',
                'code' => '8806090109256',
                'slug' => 'celular-samsung-galaxy-note-10-plus-ds-256-gb-plateado',
                'sm_image_url' => 'http://localhost:8000/images/products/3.jpg',
                'md_image_url' => 'http://localhost:8000/images/products/3.jpg',
                'lg_image_url' => 'http://localhost:8000/images/products/3.jpg',
                'cost' => 350000,
                'price' => 3999900,
                'unity' => 'UND',
                'quantity' => '1',
                'pum_unity' => 'UND',
                'pum_price' => 3999900,
                'active' => true,
            ],
            [
                'name' => 'iPhone 11 64 GB en negro',
                'code' => '190199221086',
                'slug' => 'iphone-11-64-gb-en-negro',
                'sm_image_url' => 'http://localhost:8000/images/products/4.png',
                'md_image_url' => 'http://localhost:8000/images/products/4.png',
                'lg_image_url' => 'http://localhost:8000/images/products/4.png',
                'cost' => 300000,
                'price' => 3449000,
                'unity' => 'UND',
                'quantity' => '1',
                'pum_unity' => 'UND',
                'pum_price' => 3449000,
                'active' => true,
            ],
            [
                'name' => 'iPad Pro 12.9" Pulgadas 256GB Wi‑Fi Gris',
                'code' => '190199416833',
                'slug' => 'ipad-pro-12-pulgadas-256gb-wi-fi-gris',
                'sm_image_url' => 'http://localhost:8000/images/products/5.jpg',
                'md_image_url' => 'http://localhost:8000/images/products/5.jpg',
                'lg_image_url' => 'http://localhost:8000/images/products/5.jpg',
                'cost' => 300000,
                'price' => 3449000,
                'unity' => 'UND',
                'quantity' => '1',
                'pum_unity' => 'UND',
                'pum_price' => 3449000,
                'active' => true,
            ],
            [
                'name' => 'Galaxy Tab S6 10,5" Pulgadas Cloud blue',
                'code' => '7707222703803',
                'slug' => 'galaxy-tab-s6-10-5-pulgadas-cloud-blue',
                'sm_image_url' => 'http://localhost:8000/images/products/6.png',
                'md_image_url' => 'http://localhost:8000/images/products/6.png',
                'lg_image_url' => 'http://localhost:8000/images/products/6.png',
                'cost' => 2800000,
                'price' => 3199900,
                'unity' => 'UND',
                'quantity' => '1',
                'pum_unity' => 'UND',
                'pum_price' => 3199900,
                'active' => true,
            ],
            [
                'name' => 'Consola XBOX ONE S 1 Tera + 1 Control Inalámbrico+ Juego Digital Halo 5 Guardians + Game Pass 1 Mes+ Xbox Live Gold 14 días',
                'code' => '889842105049',
                'slug' => 'consola-xbox-one-s-1-tera-1-control-inalambrico',
                'sm_image_url' => 'http://localhost:8000/images/products/7.jpg',
                'md_image_url' => 'http://localhost:8000/images/products/7.jpg',
                'lg_image_url' => 'http://localhost:8000/images/products/7.jpg',
                'cost' => 1000000,
                'price' => 1399900,
                'unity' => 'UND',
                'quantity' => '1',
                'pum_unity' => 'UND',
                'pum_price' => 1399900,
                'active' => true,
            ],
            [
                'name' => 'Consola Nintendo Switch con Joy Con Neon/Blue',
                'code' => '045496882174',
                'slug' => 'consola-nintendo-switch-con-joy-con-neon-blue',
                'sm_image_url' => 'http://localhost:8000/images/products/8.jpg',
                'md_image_url' => 'http://localhost:8000/images/products/8.jpg',
                'lg_image_url' => 'http://localhost:8000/images/products/8.jpg',
                'cost' => 1200000,
                'price' => 1694000,
                'unity' => 'UND',
                'quantity' => '1',
                'pum_unity' => 'UND',
                'pum_price' => 1694000,
                'active' => true,
            ],
        ];

        $products = array_map(fn($product) => Product::updateOrCreate(Arr::only($product, ['code']), $product), $products);

        array_map(
            fn($products, $categoryId) => Category::find($categoryId + 1)->products()->sync(data_get($products, '*.id')),
            array_chunk($products, 2), array_keys(array_chunk($products, 2))
        );
    }
}
