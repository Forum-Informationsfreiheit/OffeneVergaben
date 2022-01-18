<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAppColumnsToFifScraperDbTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // !!! USE DIFFERENT DB CONNECTION: mysql_scraper !!!
        // Need to define the 'write-back' fields in the scraper table which are used
        // as the connection between the scraper database and the app's database

        Schema::connection('mysql_scraper')->table('kerndaten', function(Blueprint $table)
        {
            $table->timestamp('app_processed_at')->nullable();
            $table->unsignedBigInteger('app_dataset_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_scraper')->table('kerndaten', function(Blueprint $table)
        {
            $table->dropColumn('app_dataset_id');
            $table->dropColumn('app_processed_at');
        });
    }
}
