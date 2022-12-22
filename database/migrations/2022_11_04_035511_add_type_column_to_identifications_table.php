<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTypeColumnToIdentificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('identifications', function (Blueprint $table) {
            $table->string('type')->default('code')->after('employee_id');
            $table->dateTime('expiration_date')->default(DB::raw('CURRENT_TIMESTAMP'))->after('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('identifications', function (Blueprint $table) {
            $table->dropColumn(['type', 'expiration_date']);
        });
    }
}
