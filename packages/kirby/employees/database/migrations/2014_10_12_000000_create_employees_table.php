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
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unique();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->string('code')->unique();
            $table->string('identification_number')->unique();
            $table->string('position');
            $table->string('location');
            $table->string('address');
            $table->bigInteger('salary');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
