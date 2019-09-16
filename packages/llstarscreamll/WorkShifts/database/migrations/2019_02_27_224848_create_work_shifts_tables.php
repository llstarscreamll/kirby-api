<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateWorkShiftsTables.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateWorkShiftsTables extends Migration
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
            $table->unsignedInteger('grace_minutes_before_start_times')->default(10);
            $table->unsignedInteger('grace_minutes_after_start_times')->default(10);
            $table->unsignedInteger('grace_minutes_before_end_times')->default(10);
            $table->unsignedInteger('grace_minutes_after_end_times')->default(10);
            $table->unsignedInteger('meal_time_in_minutes')->default(0);
            $table->unsignedInteger('min_minutes_required_to_discount_meal_time')->default(0);
            $table->string('applies_on_days')->default('');
            $table->json('time_slots')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_work_shift', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedInteger('work_shift_id');

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('work_shift_id')->references('id')->on('work_shifts')->onDelete('cascade');
            $table->unique(['employee_id', 'work_shift_id']);
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
        Schema::dropIfExists('employee_work_shift');
        Schema::dropIfExists('work_shifts');
    }
}
