<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Die Textsuche prueft national_id/reference_number auf Gleichheit. Solange diese
 * Bedingungen als OR im Join hingen, konnte kein Index greifen; als eigene Subquery
 * (DatasetFilter::search()) koennen sie es.
 */
class AddSearchLookupIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offerors', function (Blueprint $table) {
            $table->index('national_id','ix_national_id');
            $table->index('reference_number','ix_reference_number');
        });

        Schema::table('contractors', function (Blueprint $table) {
            $table->index('national_id','ix_national_id');
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
            $table->dropIndex('ix_national_id');
        });

        Schema::table('offerors', function (Blueprint $table) {
            $table->dropIndex('ix_reference_number');
            $table->dropIndex('ix_national_id');
        });
    }
}
