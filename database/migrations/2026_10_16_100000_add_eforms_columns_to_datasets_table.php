<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Seit Oktober 2026 veroeffentlichen erste Auftraggeber (vemap, Monopolverwaltung) eForms-Bekanntmachungen
 * statt Kerndaten. Beide Formate werden auf dieselben Spalten abgebildet (type_code ist auch bei eForms ein
 * KD_*-Typ), dazu kommen:
 *
 * - source_format: 'kerndaten' oder 'eforms'. Bestehende Datensaetze sind alle Kerndaten.
 * - source_subtype: bei eForms der Notice-Subtyp (efac:NoticeSubType, z.B. '29' Vergabebekanntmachung nach
 *   RL 2014/24/EU, 'E4' freiwillige Vergabebekanntmachung). Bei Kerndaten NULL, dort sagt type_code alles.
 * - further_information: bei eForms alle Angaben, fuer die es keine Spalte gibt, lesbar mit den deutschen
 *   Bezeichnungen des eForms SDK (siehe App\EformsFurtherInformation). Laesst sich jederzeit aus dem XML
 *   neu erzeugen (php artisan fif:update-from-xml --further-information).
 * - search_text: bei eForms Titel und Beschreibungen der Lose, fuer die Volltextsuche (siehe
 *   App\EformsFurtherInformation::SEARCH_FIELDS). Wird mit --further-information ebenfalls neu erzeugt.
 */
class AddEformsColumnsToDatasetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // MySQL baut datasets dafuer neu auf: wegen der Volltextindizes geht ALGORITHM=INSTANT nicht
        // (lokal ca. 1 Minute fuer 260.000 Datensaetze, so lange ist die Tabelle fuer Schreibzugriffe gesperrt)
        Schema::table('datasets', function (Blueprint $table) {
            $table->string('source_format', 20)->default('kerndaten');
            $table->string('source_subtype', 10)->nullable();
            $table->json('further_information')->nullable();
            $table->text('search_text')->nullable();
        });

        // wie ft_title_desc (Migration 2026_09_21_150000); bei Kerndaten ist die Spalte leer, der Index bleibt klein
        DB::statement('ALTER TABLE datasets ADD FULLTEXT ft_search_text (search_text)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropIndex('ft_search_text');
            $table->dropColumn(['source_format', 'source_subtype', 'further_information', 'search_text']);
        });
    }
}
