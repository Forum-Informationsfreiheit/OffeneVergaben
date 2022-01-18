<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCpvDatasetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cpv_dataset', function (Blueprint $table) {
            $table->string('cpv_code')->length(8);
            $table->unsignedBigInteger('dataset_id');
            $table->boolean('main')->default(0);

            $table->foreign('cpv_code')->references('code')->on('cpvs')->onDelete('cascade');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');

            $table->primary(['cpv_code','dataset_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cpv_dataset');
    }
}
