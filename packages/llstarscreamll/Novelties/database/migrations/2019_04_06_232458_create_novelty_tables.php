<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
        Schema::create('novelty_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('context_type')->nullable();
            $table->string('apply_on_days_of_type')->nullable();
            $table->json('apply_on_time_slots')->nullable();
            $table->string('operator')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('novelties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('time_clock_log_id')->nullable();
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('novelty_type_id');
            $table->integer('total_time_in_minutes');
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
        Schema::dropIfExists('novelties');
        Schema::dropIfExists('novelty_types');
    }
}
