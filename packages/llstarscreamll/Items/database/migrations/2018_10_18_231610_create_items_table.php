<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateItemsTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('measure_unit_id')->unsigned();
            $table->integer('tax_id')->unsigned()->nullable();
            $table->double('purchase_price', 10, 4);
            $table->double('sale_price', 10, 4);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('measure_unit_id')->references('id')->on('measure_units');
            $table->foreign('tax_id')->references('id')->on('taxes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('items');
    }
}
