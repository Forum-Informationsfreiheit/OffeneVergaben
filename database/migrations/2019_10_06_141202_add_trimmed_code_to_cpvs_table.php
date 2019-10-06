<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrimmedCodeToCpvsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cpvs', function (Blueprint $table) {
            $table->string('trimmed_code')->length(8)->nullable()->after('code')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cpvs', function (Blueprint $table) {
            $table->dropColumn('trimmed_code');
        });
    }
}
