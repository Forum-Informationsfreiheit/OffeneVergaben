<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatasourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('datasources', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('origin_id');
            $table->string('reference_id');
            $table->string('url',500);
            $table->unsignedInteger('version_scraped');
            $table->unsignedInteger('version');             // processed version
            $table->timestamp('last_modified_at')->nullable();
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamps();

            $table->foreign('origin_id')->references('id')->on('origins')->onDelete('cascade');

            $table->unique(['origin_id','reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('datasources');
    }
}
