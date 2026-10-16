<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * /branchen gruppiert nach den ersten Stellen des CPV-Codes. Ueber LEFT(cpv_code,n)
 * kann kein Index greifen, die Seite hat deshalb jedes Mal alle aktuellen Datensaetze
 * gelesen und sortiert.
 *
 * Die oberste Ebene gruppiert immer nach zwei Stellen, dafuer gibt es jetzt eine
 * generierte Spalte, ueber die MySQL in Indexreihenfolge gruppieren kann. Die tieferen
 * Ebenen haben eine wechselnde Breite (level + 1) und lassen sich nicht auf eine Spalte
 * festlegen; sie bekommen einen abdeckenden Index, der zumindest das Lesen der Tabelle
 * erspart -- sortiert werden muss dort weiterhin.
 */
class AddCpvGroupingIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // virtual, nicht stored: cpv_code haengt an einem Fremdschluessel mit
        // ON DELETE SET NULL, und darauf laesst MySQL keine gespeicherte generierte
        // Spalte zu (ERROR 1215). Der Index materialisiert den Wert ohnehin.
        DB::statement('ALTER TABLE datasets
            ADD COLUMN cpv_div varchar(2) GENERATED ALWAYS AS (LEFT(`cpv_code`,2)) VIRTUAL,
            ADD INDEX ix_cur_dis_cpvdiv_val (is_current_version, disabled_at, cpv_div, val_total)');

        Schema::table('datasets', function (Blueprint $table) {
            $table->index(['is_current_version','disabled_at','cpv_code','val_total'],'ix_cur_dis_cpv_val');
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
            $table->dropIndex('ix_cur_dis_cpv_val');
        });

        DB::statement('ALTER TABLE datasets
            DROP INDEX ix_cur_dis_cpvdiv_val,
            DROP COLUMN cpv_div');
    }
}
