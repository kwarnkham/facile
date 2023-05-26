<?php

use App\Enums\PurchaseStatus;
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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->integer('purchasable_id');
            $table->string('purchasable_type');
            $table->double('price');
            $table->integer('quantity')->default(1);
            $table->tinyInteger('status')->default(PurchaseStatus::NORMAL->value);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->string('name');
            $table->string('picture')->nullable();
            $table->integer('group')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchases');
    }
};
