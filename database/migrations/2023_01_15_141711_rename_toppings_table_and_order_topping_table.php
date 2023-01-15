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
        Schema::table('order_topping', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['topping_id']);
            $table->renameColumn('topping_id', 'service_id');
        });

        Schema::rename('order_topping', 'order_service');

        Schema::rename('toppings', 'services');
        Schema::table('services', function (Blueprint $table) {
            $table->renameIndex('toppings_name_unique', 'services_name_unique');
        });
        Schema::table('order_service', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('service_id')->references('id')->on('services');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_service', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'topping_id');
        });

        Schema::rename('order_service', 'order_topping');

        Schema::rename('services', 'toppings');
        Schema::table('toppings', function (Blueprint $table) {
            $table->renameIndex('services_name_unique', 'toppings_name_unique');
        });
        Schema::table('order_topping', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('topping_id')->references('id')->on('toppings');
        });
    }
};
