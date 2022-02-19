<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHolidaysTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateHolidaysTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->increments('id');
            $table->string('country_code', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('holidays');
    }
}
