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
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_clock_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedInteger('work_shift_id');
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at');
            $table->unsignedBigInteger('checked_in_by_id');
            $table->unsignedBigInteger('checked_out_by_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('users');
            $table->foreign('work_shift_id')->references('id')->on('work_shifts');
            $table->foreign('checked_in_by_id')->references('id')->on('users');
            $table->foreign('checked_out_by_id')->references('id')->on('users');
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
