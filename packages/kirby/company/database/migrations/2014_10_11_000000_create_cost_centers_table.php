<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateCostCentersTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateCostCentersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('cost_centers');
    }
}
