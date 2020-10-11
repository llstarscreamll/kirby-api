<?php

namespace Tests\Feature\Commands;

use Kirby\Products\Models\Product;
use Tests\TestCase;

/**
 * Class SyncProductsByCsvTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SyncProductsByCsvTest extends TestCase
{
    /**
     * @var string
     */
    private $filePath = '/tmp/importer-tests.csv';

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        @unlink('/tmp/importer-tests.csv');
    }

    /**
     * @test
     */
    public function shouldCreateProductsFromFileIfTheyDoesNotExistsOnDb()
    {
        $file = [
            implode(';', ['code', 'name', 'cost', 'price', 'unity', 'quantity', 'pum_unity', 'pum_price', 'active', 'categories']),
            implode(';', ['A1', 'Mouse', '400000', '450000', 'UND', '1', 'UND', '450000', 1, 'Computers']),
            implode(';', ['B2', 'Keyboard', '520000', '600000', 'UND', '1', 'UND', '600000', 1, 'Computers']),
        ];

        file_put_contents($this->filePath, implode("\n", $file));

        $this->artisan('products:sync-by-csv', ['csv-path' => $this->filePath])
            ->assertExitCode(0)
            ->expectsOutput('2 products created successfully')
            ->expectsOutput('0 products updated successfully');

        $this->assertDatabaseRecordsCount(2, 'products');
        $this->assertDatabaseHas('products', [
            'code' => 'A1',
            'name' => 'Mouse',
            'cost' => '400000',
            'price' => '450000',
            'unity' => 'UND',
            'quantity' => '1',
            'pum_unity' => 'UND',
            'pum_price' => '450000',
            'sm_image_url' => config('shop.default-product-image'),
            'md_image_url' => config('shop.default-product-image'),
            'lg_image_url' => config('shop.default-product-image'),
            'active' => 1,
        ]);
        $this->assertDatabaseHas('products', [
            'code' => 'B2',
            'name' => 'Keyboard',
            'cost' => '520000',
            'price' => '600000',
            'unity' => 'UND',
            'quantity' => '1',
            'pum_unity' => 'UND',
            'pum_price' => '600000',
            'sm_image_url' => config('shop.default-product-image'),
            'md_image_url' => config('shop.default-product-image'),
            'lg_image_url' => config('shop.default-product-image'),
            'active' => 1,
        ]);
    }

    /**
     * @test
     */
    public function shouldUpdateProductsFromFileIfTheyAlreadyExistsOnDb()
    {
        // product to update
        $product = factory(Product::class)->create(['code' => 'A1', 'active' => false]);

        $file = [
            implode(';', ['code', 'name', 'cost', 'price', 'unity', 'quantity', 'pum_unity', 'pum_price', 'active', 'categories']),
            implode(';', ['A1', 'Mouse', '400000', '450000', 'UND', '1', 'UND', '450000', 1, 'Computers']),
        ];

        file_put_contents($this->filePath, implode("\n", $file));

        $this->artisan('products:sync-by-csv', ['csv-path' => $this->filePath])
            ->assertExitCode(0)
            ->expectsOutput('0 products created successfully')
            ->expectsOutput('1 products updated successfully');

        $this->assertDatabaseRecordsCount(1, 'products');
        $this->assertDatabaseHas('products', [
            'code' => 'A1',
            'name' => 'Mouse',
            'cost' => '400000',
            'price' => '450000',
            'unity' => 'UND',
            'quantity' => '1',
            'pum_unity' => 'UND',
            'pum_price' => '450000',
            'sm_image_url' => $product->sm_image_url,
            'md_image_url' => $product->md_image_url,
            'lg_image_url' => $product->lg_image_url,
            'active' => true,
        ]);
    }

    /**
     * @test
     */
    public function shouldCreateFileCategoriesIfDoesNotExists()
    {
        $file = [
            implode(';', ['code', 'name', 'cost', 'price', 'unity', 'quantity', 'pum_unity', 'pum_price', 'active', 'categories']),
            implode(';', ['A1', 'Mouse', '400000', '450000', 'UND', '1', 'UND', '450000', 1, 'Laptops & Desktops']),
        ];

        file_put_contents($this->filePath, implode("\n", $file));

        $this->artisan('products:sync-by-csv', ['csv-path' => $this->filePath])
            ->assertExitCode(0)
            ->expectsOutput('1 products created successfully')
            ->expectsOutput('0 products updated successfully');

        $this->assertDatabaseRecordsCount(1, 'categories');
        $this->assertDatabaseRecordsCount(1, 'category_product');
        $this->assertDatabaseHas('categories', [
            'name' => 'Laptops & Desktops',
            'slug' => 'laptops-desktops',
            'image_url' => null,
            'position' => 9999,
            'active' => true,
        ]);
    }
}