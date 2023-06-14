<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTruckScaleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weighings', function (Blueprint $table) {
            $table->id();
            $table->string('weighing_type', 20);
            $table->string('vehicle_plate', 10);
            $table->string('vehicle_type', 20);
            $table->unsignedBigInteger('driver_dni_number');
            $table->string('driver_name');
            $table->decimal('tare_weight')->default(0)->comment('measure unit in Kg');
            $table->decimal('gross_weight')->default(0)->comment('measure unit in Kg');
            $table->string('weighing_description')->default('');
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->default(0);
            $table->string('status', 10);
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
        Schema::dropIfExists('weighings');
    }
}
