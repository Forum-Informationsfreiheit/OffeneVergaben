<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * OrganizationMatcher::find() sucht Organisationen ohne gueltige Kennung ueber
 * `WHERE name = ?`. Ohne Index war das ein Full Table Scan pro Offeror/Contractor
 * in fif:process.
 *
 * name ist eine text-Spalte, daher ein Praefix-Index. Exakte Vergleiche mit = koennen
 * ihn nutzen; MySQL vergleicht danach den vollen Wert auf den gefundenen Zeilen.
 */
class AddOrganizationNameIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE organizations ADD INDEX organizations_name_idx (name(191))');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE organizations DROP INDEX organizations_name_idx');
    }
}
