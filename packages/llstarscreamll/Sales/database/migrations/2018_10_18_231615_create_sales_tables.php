<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateSalesTables.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateSalesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('seller_id')->unsigned();
            $table->integer('customer_id')->unsigned()->nullable();
            $table->integer('shipping_to_id')->unsigned()->nullable();
            $table->integer('stockroom_id')->unsigned();
            $table->integer('status_id')->unsigned();
            $table->datetime('issue_date');
            $table->datetime('shipment_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('seller_id')->references('id')->on('users');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('shipping_to_id')->references('id')->on('shippings');
            $table->foreign('stockroom_id')->references('id')->on('stockrooms');
            $table->foreign('status_id')->references('id')->on('sale_statuses');
        });

        Schema::create('item_sale', function (Blueprint $table) {
            $table->integer('sale_id')->unsigned();
            $table->integer('item_id')->unsigned();
            $table->double('price', 10, 4);
            $table->integer('quantity');
            $table->integer('tax');
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales');
            $table->foreign('item_id')->references('id')->on('items');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales');
        Schema::dropIfExists('item_sale');
    }
}
