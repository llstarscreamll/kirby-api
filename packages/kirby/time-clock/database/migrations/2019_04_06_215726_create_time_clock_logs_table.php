<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateTimeClockLogsTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateTimeClockLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('time_clock_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedInteger('sub_cost_center_id')->nullable();
            $table->unsignedInteger('work_shift_id')->nullable();
            $table->datetime('checked_in_at');
            $table->datetime('expected_check_in_at')->nullable();
            $table->unsignedInteger('check_in_novelty_type_id')->nullable();
            $table->unsignedInteger('check_in_sub_cost_center_id')->nullable();
            $table->datetime('checked_out_at')->nullable();
            $table->datetime('expected_check_out_at')->nullable();
            $table->unsignedInteger('check_out_novelty_type_id')->nullable();
            $table->unsignedInteger('check_out_sub_cost_center_id')->nullable();
            $table->unsignedBigInteger('checked_in_by_id');
            $table->unsignedBigInteger('checked_out_by_id')->nullable();
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
     */
    public function down()
    {
        Schema::dropIfExists('time_clock_logs');
    }
}
