<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatasetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('datasets', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('datasource_id');
            $table->foreign('datasource_id')->references('id')->on('datasources')->onDelete('cascade');
            $table->unsignedInteger('version');
            $table->unique(['datasource_id','version']);

            $table->string('type_code')->length(15)->nullable();
            $table->foreign('type_code')->references('code')->on('dataset_types')->onDelete('set null');
            $table->string('cpv_code')->length(8)->nullable();
            $table->foreign('cpv_code')->references('code')->on('cpvs')->onDelete('set null');
            $table->string('nuts_code')->length(10)->nullable();
            $table->foreign('nuts_code')->references('code')->on('nuts')->onDelete('set null');

            // DETAILS ----- COULD BE MOVED INTO OWN TABLE LATER ON
            $table->string('url_document',500)->nullable();
            $table->boolean('url_is_restricted')->nullable();
            $table->string('url_participation',500)->nullable();

            $table->string('contract_type',100)->nullable();
            $table->string('title',1000)->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('datasets');
    }
}
