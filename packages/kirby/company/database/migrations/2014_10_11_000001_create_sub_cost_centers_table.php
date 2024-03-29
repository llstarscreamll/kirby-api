<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateSubCostCentersTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateSubCostCentersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sub_cost_centers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('cost_center_id');
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cost_center_id')->references('id')->on('cost_centers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('sub_cost_centers');
    }
}
