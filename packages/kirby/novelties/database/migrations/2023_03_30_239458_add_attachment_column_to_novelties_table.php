<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddAttachmentColumnToNoveltiesTable.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class AddAttachmentColumnToNoveltiesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('novelties', function (Blueprint $table) {
            $table->json('attachment')->after('comment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('novelties', function (Blueprint $table) {
            $table->dropColumn('attachment');
        });
    }
}
