<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kirby\Production\Enums\Purpose;

class AddPurposeAttributeToProductionLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->string('purpose', 100)->after('customer_id')->default(Purpose::Consumption);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->dropColumn(['purpose']);
        });
    }
}
