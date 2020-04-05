<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDatasetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Scraper Umstellung, datasources table wird nicht mehr benötigt, alle keys und column daher löschen
        // scraper results liegen in eigener database(!) daher kein foreign key mehr möglich

        Schema::table('datasets', function (Blueprint $table) {
            //NOTE as needed
            $table->dropForeign('datasets_datasource_id_foreign');
            $table->dropForeign('datasets_result_id_foreign');

            $table->dropUnique('datasets_datasource_id_version_unique');

            $table->dropColumn('datasource_id');

            $table->renameColumn('result_id','scraper_kerndaten_id');
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
            $table->unsignedInteger('datasource_id')->after('id')->nullable();

            // NOTE the following two commands won't work if the tables contain data
            //$table->foreign('datasource_id')->references('id')->on('datasources')->onDelete('cascade');
            //$table->foreign('result_id')->references('id')->on('scraper_results')->onDelete('set null');

            $table->unique(['datasource_id','version']);
        });
    }
}
