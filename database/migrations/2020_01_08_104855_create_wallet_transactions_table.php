<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('trans_ref_id');
            $table->float('amount');
            $table->boolean('is_credit')->default(false);
            $table->string('wallet_id')->nullable();
            $table->string('company_id')->nullable();;
            $table->string('card_no')->nullable();;
            $table->string('description')->nullable();;
            $table->string('initiator_id')->nullable();;
            $table->string('source')->nullable();;
            $table->string('destination')->nullable();;
            $table->string('destination_id')->nullable();;
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
        Schema::dropIfExists('wallet_transactions');
    }
}
