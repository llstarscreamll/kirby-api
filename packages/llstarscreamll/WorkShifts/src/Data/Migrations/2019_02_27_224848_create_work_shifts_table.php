<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateWorkShiftsTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateWorkShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('grace_minutes_for_start_time')->nullable()->default(15);
            $table->unsignedInteger('grace_minutes_for_end_time')->nullable()->default(15);
            $table->unsignedInteger('meal_time_in_minutes')->nullable()->default(0);
            $table->unsignedInteger('min_minutes_required_to_discount_meal_time')->nullable()->default(0);
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
        Schema::dropIfExists('work_shifts');
    }
}
