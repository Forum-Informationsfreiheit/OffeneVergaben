<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Index-Arbeit aus dem Benchmark vom 2026-09-21.
 *
 * Die Index-Liste der /aufträge Seite hat vorher die komplette datasets-Tabelle
 * (262k Zeilen) per filesort sortiert, weil zu is_current_version/disabled_at
 * keine Sortierspalte im Index stand. Dasselbe beim Sortieren nach Bieter-Namen:
 * offerors.name ist TEXT, ein Prefix-Index kann ORDER BY nicht bedienen. Daher
 * eine generierte Spalte mit den ersten 191 Zeichen, auf die sortiert wird.
 */
class AddPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->index(['is_current_version','disabled_at','item_lastmod'],'ix_cur_dis_lastmod');
            $table->index(['is_current_version','disabled_at','val_total'],'ix_cur_dis_val');

            // exaktes Duplikat von is_current_version
            $table->dropIndex('is_current_version_2');
        });

        // name ist TEXT und laenger als 255 (max 398 bei offerors, 2056 bei contractors),
        // laesst sich also weder auf varchar verkuerzen noch direkt indizieren
        Schema::table('offerors', function (Blueprint $table) {
            $table->dropIndex('name');
        });
        DB::statement('ALTER TABLE offerors
            ADD COLUMN name_sort varchar(191) GENERATED ALWAYS AS (LEFT(`name`,191)) STORED,
            ADD INDEX ix_extra_namesort_ds (is_extra, name_sort, dataset_id)');

        Schema::table('contractors', function (Blueprint $table) {
            $table->dropIndex('name');
        });
        DB::statement('ALTER TABLE contractors
            ADD COLUMN name_sort varchar(191) GENERATED ALWAYS AS (LEFT(`name`,191)) STORED,
            ADD INDEX ix_extra_namesort_ds (is_extra, name_sort, dataset_id)');

        // Der reine FK-Index auf dataset_id ist ein Prefix des Index (dataset_id, is_extra).
        // Solange beide existieren greift der Optimizer beim count(*) der Seitenzahlen zum
        // FK-Index und muss danach die Zeile fuer is_extra nachladen; mit nur dem
        // zusammengesetzten Index bleibt der Lookup covering (710ms -> 484ms gemessen).
        // Der Foreign Key bleibt gedeckt, weil dataset_id dessen erste Spalte ist.
        DB::statement('ALTER TABLE offerors DROP INDEX offerors_dataset_id_foreign');
        DB::statement('ALTER TABLE contractors DROP INDEX contractors_dataset_id_foreign');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE contractors ADD INDEX contractors_dataset_id_foreign (dataset_id)');
        DB::statement('ALTER TABLE offerors ADD INDEX offerors_dataset_id_foreign (dataset_id)');

        DB::statement('ALTER TABLE contractors
            DROP INDEX ix_extra_namesort_ds,
            DROP COLUMN name_sort');
        DB::statement('ALTER TABLE offerors
            DROP INDEX ix_extra_namesort_ds,
            DROP COLUMN name_sort');

        DB::statement('ALTER TABLE contractors ADD INDEX `name` (`name`(20))');
        DB::statement('ALTER TABLE offerors ADD INDEX `name` (`name`(20))');

        Schema::table('datasets', function (Blueprint $table) {
            $table->index(['is_current_version','disabled_at'],'is_current_version_2');
            $table->dropIndex('ix_cur_dis_val');
            $table->dropIndex('ix_cur_dis_lastmod');
        });
    }
}
