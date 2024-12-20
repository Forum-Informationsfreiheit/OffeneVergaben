<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatColumnsToOrganizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedInteger('count_offeror')->default(0)->after('is_identified');
            $table->unsignedInteger('count_contractor')->default(0)->after('count_offeror');
            $table->unsignedInteger('count_ausschreibung_offeror')->default(0)->after('count_contractor');
            $table->unsignedInteger('count_ausschreibung_contractor')->default(0)->after('count_ausschreibung_offeror');
            $table->unsignedInteger('count_auftrag_offeror')->default(0)->after('count_ausschreibung_contractor');
            $table->unsignedInteger('count_auftrag_contractor')->default(0)->after('count_auftrag_offeror');

            // auftragsvolumen bei ausschreibungen ist immer NULL --> es gibt keinen grund diese felder mitzufuehren
//            $table->unsignedBigInteger('val_total_ausschreibung_offeror')->default(0);
//            $table->unsignedBigInteger('val_total_ausschreibung_contractor')->default(0);
            $table->unsignedBigInteger('val_total_auftrag_offeror')->default(0)->after('count_auftrag_contractor');
            $table->unsignedBigInteger('val_total_auftrag_contractor')->default(0)->after('val_total_auftrag_offeror');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('val_total_auftrag_contractor');
            $table->dropColumn('val_total_auftrag_offeror');
            $table->dropColumn('count_auftrag_contractor');
            $table->dropColumn('count_auftrag_offeror');
            $table->dropColumn('count_ausschreibung_contractor');
            $table->dropColumn('count_ausschreibung_offeror');
            $table->dropColumn('count_contractor');
            $table->dropColumn('count_offeror');
        });
    }
}
