<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_payment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained();
            $table->foreignId('order_id')->constrained();
            $table->double('amount');
            $table->string('number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('payment_name')->nullable();
            $table->string('note')->nullable();
            $table->string('picture')->nullable();
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
        Schema::dropIfExists('order_payment');
    }
};
