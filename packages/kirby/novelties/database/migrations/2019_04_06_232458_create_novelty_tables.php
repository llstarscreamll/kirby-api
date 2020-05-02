<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateNoveltyTables.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltyTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('novelties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('time_clock_log_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedInteger('novelty_type_id');
            $table->unsignedInteger('sub_cost_center_id')->nullable();
            $table->dateTime('scheduled_start_at')->nullable();
            $table->dateTime('scheduled_end_at')->nullable();
            $table->string('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('time_clock_log_id')->references('id')->on('time_clock_logs')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('novelty_type_id')->references('id')->on('novelty_types')->onDelete('cascade');
            $table->foreign('sub_cost_center_id')->references('id')->on('sub_cost_centers')->onDelete('cascade');
        });

        Schema::create('novelty_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('novelty_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['novelty_id', 'user_id']);
            $table->foreign('novelty_id')->references('id')->on('novelties')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('novelty_approvals');
        Schema::dropIfExists('novelties');
    }
}
