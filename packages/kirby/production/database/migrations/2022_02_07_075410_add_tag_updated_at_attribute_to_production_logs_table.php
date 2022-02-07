<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kirby\Production\Enums\Tag;

class AddTagUpdatedAtAttributeToProductionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->dateTime('tag_updated_at')->after('tag')->useCurrent();
        });

        DB::table('production_logs')->update(['tag_updated_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->dropColumn(['tag_updated_at']);
        });
    }
}
