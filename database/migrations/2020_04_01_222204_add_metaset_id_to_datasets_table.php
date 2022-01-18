<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMetasetIdToDatasetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->unsignedInteger('metaset_id')->after('id')->nullable();

            $table->foreign('metaset_id')->references('id')->on('metasets')->onDelete('SET NULL');

            $table->timestamp('item_lastmod',6)->nullable()->after('nb_participants_sme');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropForeign('datasets_metaset_id_foreign');
            $table->dropColumn('metaset_id');
            $table->dropColumn('item_lastmod');
        });
    }
}
