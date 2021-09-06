<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('production_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->references('id')->on('products');
            $table->foreignId('machine_id')->references('id')->on('machines');
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->foreignId('customer_id')->nullable()->references('id')->on('customers');
            $table->integer('batch')->unsigned()->nullable();
            $table->decimal('tare_weight')->comment('measure unit in Kg');
            $table->decimal('gross_weight')->comment('measure unit in Kg');
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
        Schema::dropIfExists('production_logs');
    }
}
