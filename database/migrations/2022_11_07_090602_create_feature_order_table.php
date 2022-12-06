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
        Schema::create('feature_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained();
            $table->foreignId('order_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->double('price');
            $table->double('discount')->default(0);
            $table->foreignId('batch_id')->constrained();
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
        Schema::dropIfExists('feature_order');
    }
};
