<?php

use App\Enums\ProductStatus;
use App\Enums\ProductType;
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
        Schema::create('a_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('stock')->index();
            $table->double('price');
            $table->string('note')->nullable();
            $table->tinyInteger('type')->default(ProductType::STOCKED->value);
            $table->tinyInteger('status')->default(ProductStatus::NORMAL->value);
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
        Schema::dropIfExists('a_items');
    }
};
