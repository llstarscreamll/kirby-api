<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateNoveltyTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltyTypesTable extends Migration
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
            $table->string('time_zone')->default('UTC');
            $table->string('apply_on_days_of_type')->nullable();
            $table->json('apply_on_time_slots')->nullable();
            $table->string('operator')->nullable();
            $table->boolean('requires_comment')->default(false);
            $table->boolean('keep_in_report')->default(true);
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
        Schema::dropIfExists('novelty_types');
    }
}
