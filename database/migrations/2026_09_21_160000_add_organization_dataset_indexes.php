<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Die Statistiken auf /auftraggeber/{id} und /lieferanten/{id} suchen ueber
 * organization_id und brauchen danach nur die dataset_id. Mit dem zusammengesetzten
 * Index bleibt der Lookup covering, vorher wurde fuer jede Zeile der Datensatz
 * nachgeladen (1898ms -> 716ms bei der groessten Organisation im Benchmark).
 */
class AddOrganizationDatasetIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offerors', function (Blueprint $table) {
            $table->index(['organization_id','dataset_id'],'ix_org_dataset');
        });

        Schema::table('contractors', function (Blueprint $table) {
            $table->index(['organization_id','dataset_id'],'ix_org_dataset');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropIndex('ix_org_dataset');
        });

        Schema::table('offerors', function (Blueprint $table) {
            $table->dropIndex('ix_org_dataset');
        });
    }
}
