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
        Schema::dropIfExists('credits');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_payment_id')->constrained('order_payment');
            $table->double('amount');
            $table->string('note')->nullable();
            $table->string('number')->nullable();
            $table->timestamps();
        });
    }
};
