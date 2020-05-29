<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatasetProcedureTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dataset_procedure', function (Blueprint $table) {
            $table->string('procedure_code')->length(50);
            $table->unsignedBigInteger('dataset_id');

            $table->foreign('procedure_code')->references('code')->on('procedures')->onDelete('cascade');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');

            $table->primary(['procedure_code','dataset_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dataset_procedure');
    }
}
