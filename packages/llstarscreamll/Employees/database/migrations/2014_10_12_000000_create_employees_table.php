<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateEmployeesTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->unsignedInteger('id')->unique();
            $table->unsignedInteger('cost_center_id')->nullable();
            $table->string('code');
            $table->string('identification_number');
            $table->string('position');
            $table->string('location');
            $table->string('address');
            $table->string('phone');
            $table->bigInteger('salary');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id')->references('id')->on('users');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
