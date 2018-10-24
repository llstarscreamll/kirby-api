<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateStockroomsTables.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateStockroomsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stockrooms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('address', 255);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('item_stockroom', function (Blueprint $table) {
            $table->integer('stockroom_id')->unsigned();
            $table->integer('item_id')->unsigned();
            $table->bigInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('stockroom_id')->references('id')->on('stockrooms');
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
        Schema::dropIfExists('stockrooms');
        Schema::dropIfExists('item_stockroom');
    }
}
