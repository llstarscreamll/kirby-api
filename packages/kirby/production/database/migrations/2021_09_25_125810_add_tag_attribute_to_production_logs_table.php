<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kirby\Production\Enums\Tag;

class AddTagAttributeToProductionLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->string('tag')->after('customer_id')->default(Tag::InLine());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->dropColumn(['tag']);
        });
    }
}
