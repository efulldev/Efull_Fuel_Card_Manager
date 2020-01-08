<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFillingStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('filling_stations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('station_name');
            $table->string('wallet_id');
            $table->mediumText('station_address');
            $table->string('station_phone');
            $table->string('station_email');
            $table->string('reg_number');
            $table->boolean('is_active');
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
        Schema::dropIfExists('filling_stations');
    }
}
