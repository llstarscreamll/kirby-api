<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelDescriptionColumnToWeighingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('weighings', function (Blueprint $table) {
            $table->string('cancel_comment')->default('')->after('weighing_description');
            $table->string('status', 15)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('weighings', function (Blueprint $table) {
            $table->dropColumn('cancel_comment');
            $table->string('status', 10)->change();
        });
    }
}
