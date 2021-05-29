<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('internal_code')->unique();
            $table->string('customer_code')->unique();
            $table->string('name');
            $table->string('wire_gauge_in_bwg')->default('')->comment('wire gauge in BGW');
            $table->unsignedDecimal('wire_gauge_in_mm')->comment('wire gauge in millimeters');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
