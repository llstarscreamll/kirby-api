<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LaravelPassport9Upgrade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('secret', 100)->nullable()->change();
            if (! Schema::hasColumn('oauth_clients', 'provider')) {
                $table->string('provider')->after('secret')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('secret', 100)->change();
            if (Schema::hasColumn('oauth_clients', 'provider')) {
                $table->dropColumn(['provider']);
            }
        });
    }
}
