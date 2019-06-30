<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOfferorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offerors', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('dataset_id');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');

            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');

            $table->boolean('is_extra')->default(0);

            // following are all data-input dependent, we don't know what we are going to get
            // so make everything nullable and let the app itself handle those null cases
            $table->string('national_id')->length(50)->nullable();
            $table->string('name')->nullable();
            $table->string('domain')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('contact')->nullable();

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
        Schema::dropIfExists('offerors');
    }
}
