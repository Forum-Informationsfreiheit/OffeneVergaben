<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * ScraperKerndaten::unprocessed() (fif:process) filters on app_processed_at IS NULL
 * AND app_dataset_id IS NULL. Without an index that was a full scan of kerndaten
 * (~280k rows for ~18k hits) on every run.
 *
 * One index on app_processed_at is enough: MySQL reads only the NULL entries and
 * checks app_dataset_id on those rows.
 */
class AddAppProcessedAtIndexToScraperKerndaten extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // !!! USE DIFFERENT DB CONNECTION: mysql_scraper !!!
        Schema::connection('mysql_scraper')->table('kerndaten', function (Blueprint $table) {
            $table->index('app_processed_at', 'kerndaten_app_processed_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_scraper')->table('kerndaten', function (Blueprint $table) {
            $table->dropIndex('kerndaten_app_processed_at_idx');
        });
    }
}
