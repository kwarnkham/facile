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
        Schema::create('a_item_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('a_item_id')->constrained();
            $table->foreignId('order_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->double('price');
            $table->double('discount')->default(0);
            $table->string('name');
            $table->double('purchase_price')->nullable();
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
        Schema::dropIfExists('a_item_order');
    }
};
