<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateOrdersTables.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateOrdersTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('payment_method')->default('cash');
            $table->string('address');
            $table->string('address_additional_info')->nullable();
            $table->timestamps();

            $table->index(['user_id']);
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name')->comment('product name copy');
            $table->string('product_code')->comment('product code copy');
            $table->string('product_slug')->comment('product slug copy');
            $table->string('product_sm_image_url')->comment('product sm_image_url copy');
            $table->string('product_md_image_url')->comment('product md_image_url copy');
            $table->string('product_lg_image_url')->comment('product lg_image_url copy');
            $table->decimal('product_cost', 19, 4)->unsigned()->comment('product cost copy');
            $table->decimal('product_price', 19, 4)->unsigned()->comment('product price copy');
            $table->string('product_unity')->comment('product unity copy');
            $table->decimal('product_quantity', 10, 4)->unsigned()->comment('product quantity copy');
            $table->string('product_pum_unity')->comment('product pum_unity copy');
            $table->decimal('product_pum_price', 19, 4)->unsigned()->comment('product pum_price copy');
            $table->integer('requested_quantity')->default(1)->comment('requested quantity by user');
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_products');
        Schema::dropIfExists('orders');
    }
}
