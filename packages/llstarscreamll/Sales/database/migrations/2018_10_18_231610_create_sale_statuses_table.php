<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateSaleStatusesTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateSaleStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('description', 255);
            $table->boolean('default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_statuses');
    }
}
