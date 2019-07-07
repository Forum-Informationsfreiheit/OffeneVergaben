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
            $table->unsignedBigInteger('result_id')->nullable();
            $table->foreign('result_id')->references('id')->on('scraper_results')->onDelete('set null');

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

            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->dateTime('datetime_receipt_tenders')->nullable();

            $table->boolean('is_lot')->nullable();

            $table->boolean('is_framework')->nullable();

            // award contract
            $table->date('date_conclusion_contract')->nullable();
            $table->unsignedInteger('nb_tenders_received')->nullable();
            $table->unsignedInteger('nb_sme_tender')->nullable();
            $table->unsignedInteger('nb_sme_contractor')->nullable();       // <-- wird auch von acd verwendet (leicht andere bedeutung)
            $table->unsignedBigInteger('val_total')->nullable();            // <-- wird auch von acd verwendet

            // modifications contract
            $table->unsignedBigInteger('val_total_before')->nullable();
            $table->unsignedBigInteger('val_total_after')->nullable();
            $table->text('info_modifications')->nullable();

            // additional core data
            $table->text('justification')->nullable();
            $table->date('date_first_publication')->nullable();
            $table->dateTime('datetime_last_change')->nullable();
            $table->date('deadline_standstill')->nullable();
            $table->boolean('rd_notification')->nullable();
            $table->string('ocm_title',1000)->nullable();
            $table->string('ocm_contract_type',100)->nullable();
            $table->text('procedure_description')->nullable();
            $table->boolean('threshold')->nullable();
            $table->string('url_revocation',500)->nullable();
            $table->string('url_revocation_statement',500)->nullable();

            // awarded prize stuff
            $table->unsignedInteger('nb_participants')->nullable();
            $table->unsignedInteger('nb_participants_sme')->nullable();

            $table->unique(['datasource_id','version']);

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
