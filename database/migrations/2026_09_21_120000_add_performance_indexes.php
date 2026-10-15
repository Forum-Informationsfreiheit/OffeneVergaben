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
        });

        // exaktes Duplikat von is_current_version
        $this->dropIndexIfExists('datasets','is_current_version_2');

        // name ist TEXT und laenger als 255 (max 398 bei offerors, 2056 bei contractors),
        // laesst sich also weder auf varchar verkuerzen noch direkt indizieren
        $this->dropIndexIfExists('offerors','name');
        DB::statement('ALTER TABLE offerors
            ADD COLUMN name_sort varchar(191) GENERATED ALWAYS AS (LEFT(`name`,191)) STORED,
            ADD INDEX ix_extra_namesort_ds (is_extra, name_sort, dataset_id)');

        $this->dropIndexIfExists('contractors','name');
        DB::statement('ALTER TABLE contractors
            ADD COLUMN name_sort varchar(191) GENERATED ALWAYS AS (LEFT(`name`,191)) STORED,
            ADD INDEX ix_extra_namesort_ds (is_extra, name_sort, dataset_id)');

        // Der reine FK-Index auf dataset_id ist ein Prefix des Index (dataset_id, is_extra).
        // Solange beide existieren greift der Optimizer beim count(*) der Seitenzahlen zum
        // FK-Index und muss danach die Zeile fuer is_extra nachladen; mit nur dem
        // zusammengesetzten Index bleibt der Lookup covering (710ms -> 484ms gemessen).
        // Der Foreign Key bleibt gedeckt, weil dataset_id dessen erste Spalte ist.
        //
        // Der zusammengesetzte Index heisst im Produktivschema `dataset_id` und wurde dort von
        // Hand angelegt, eine frisch migrierte Datenbank hat ihn nicht. Ohne ihn verweigert
        // MySQL das Loeschen des FK-Index (Fehler 1553), daher wird er bei Bedarf unter
        // demselben Namen angelegt.
        //
        // Auf einer frisch migrierten Datenbank ist der FK-Index implizit (von MySQL fuer den
        // Foreign Key angelegt) und verschwindet von selbst, sobald `dataset_id` den FK deckt.
        // Ein unbedingtes DROP INDEX bricht dort mit Fehler 1091 ab.
        foreach (['offerors','contractors'] as $table) {
            if (!$this->indexExists($table,'dataset_id')) {
                DB::statement("ALTER TABLE `$table` ADD INDEX `dataset_id` (dataset_id, is_extra)");
            }
            $this->dropIndexIfExists($table,"{$table}_dataset_id_foreign");
        }
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

    /**
     * Index nur loeschen, wenn es ihn gibt.
     *
     * is_current_version_2 und die Prefix-Indexe `name` wurden im Produktivschema von Hand
     * angelegt und stammen aus keiner Migration. Auf einer frisch migrierten Datenbank (CI,
     * Demo, Integrationstests) fehlen sie, ein unbedingtes DROP INDEX bricht dort ab.
     */
    private function dropIndexIfExists($table, $index)
    {
        if ($this->indexExists($table,$index)) {
            DB::statement("ALTER TABLE `$table` DROP INDEX `$index`");
        }
    }

    private function indexExists($table, $index)
    {
        return count(DB::select('SELECT 1 FROM information_schema.statistics
            WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',[$table,$index])) > 0;
    }
}
