<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Verfahrensarten aus eForms (Codeliste procurement-procedure-type), fuer die es in den Kerndaten keinen Code gibt
 * (siehe App\DOMEforms und procedure_label()):
 *
 * - OTH_SINGLE / OTH_MULT: eForms kennt keine oesterreichischen Verfahrensarten. Was nicht unter die Verfahren der
 *   EU-Richtlinien faellt (z.B. Direktvergabe mit vorheriger Bekanntmachung), kommt als 'oth-single' oder 'oth-mult'
 *   ("sonstiges ein-/zweistufiges Verfahren"), ohne genauere Angabe. Statt zu raten, bekommen diese eigene Codes.
 * - COMP_TEND / EXP_INT_RAIL: Verfahren fuer oeffentliche Personenverkehrsdienste nach Art. 5 Abs. 3 bzw. 3b der
 *   Verordnung (EG) Nr. 1370/2007 ('comp-tend', 'exp-int-rail').
 *
 * dataset_procedure verweist per Fremdschluessel auf procedures, die Codes muessen also existieren, bevor
 * fif:process eForms-Datensaetze schreiben kann.
 */
class AddEformsProcedureTypes extends Migration
{
    const CODES = ['OTH_SINGLE', 'OTH_MULT', 'COMP_TEND', 'EXP_INT_RAIL'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('procedures')->insertOrIgnore(array_map(function ($code) {
            return ['code' => $code, 'name' => $code];
        }, self::CODES));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // haengen schon Datensaetze daran, loescht der Fremdschluessel (ON DELETE CASCADE) nur die Zuordnung
        DB::table('procedures')->whereIn('code', self::CODES)->delete();
    }
}
