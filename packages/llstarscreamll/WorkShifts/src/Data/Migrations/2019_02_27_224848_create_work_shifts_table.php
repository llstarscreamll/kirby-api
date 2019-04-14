<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->string('name', 50)->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('grace_minutes_for_start_time')->nullable()->default(15);
            $table->unsignedInteger('grace_minutes_for_end_time')->nullable()->default(15);
            $table->unsignedInteger('meal_time_in_minutes')->nullable()->default(0);
            $table->unsignedInteger('min_minutes_required_to_discount_meal_time')->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_work_shift', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('work_shift_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('work_shift_id')->references('id')->on('work_shifts')->onDelete('cascade');
            $table->primary(['user_id', 'work_shift_id']);
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
        Schema::dropIfExists('work_shifts');
    }
}
