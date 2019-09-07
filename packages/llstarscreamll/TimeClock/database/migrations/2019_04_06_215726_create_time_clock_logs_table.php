<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateTimeClockLogsTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateTimeClockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_clock_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('sub_cost_center_id')->nullable();
            $table->unsignedInteger('work_shift_id')->nullable();
            $table->datetime('checked_in_at');
            $table->unsignedInteger('check_in_novelty_type_id')->nullable();
            $table->unsignedInteger('check_in_sub_cost_center_id')->nullable();
            $table->datetime('checked_out_at')->nullable();
            $table->unsignedInteger('check_out_novelty_type_id')->nullable();
            $table->unsignedInteger('check_out_sub_cost_center_id')->nullable();
            $table->unsignedInteger('checked_in_by_id');
            $table->unsignedInteger('checked_out_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('work_shift_id')->references('id')->on('work_shifts')->onDelete('cascade');
            $table->foreign('sub_cost_center_id')->references('id')->on('sub_cost_centers')->onDelete('cascade');
            $table->foreign('check_in_novelty_type_id')->references('id')->on('novelty_types')->onDelete('cascade');
            $table->foreign('check_in_sub_cost_center_id')->references('id')->on('sub_cost_centers')->onDelete('cascade');
            $table->foreign('check_out_novelty_type_id')->references('id')->on('novelty_types')->onDelete('cascade');
            $table->foreign('check_out_sub_cost_center_id')->references('id')->on('sub_cost_centers')->onDelete('cascade');
            $table->foreign('checked_in_by_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('checked_out_by_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_clock_logs');
    }
}
