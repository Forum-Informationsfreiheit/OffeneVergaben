<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDisabledAtToDatasetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->timestamp('disabled_at')->nullable()->after('scraper_kerndaten_id');
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
            $table->dropColumn('disabled_at');
        });
    }
}
