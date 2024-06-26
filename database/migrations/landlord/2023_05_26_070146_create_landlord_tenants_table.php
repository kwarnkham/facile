<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLandlordTenantsTable extends Migration
{
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('domain')->unique();
            $table->string('database')->unique();
            $table->timestamp('expires_on')->index()->nullable();
            $table->integer('type')->default(1);
            $table->foreignId('plan_id')->constrained();
            $table->jsonb('plan_usage');
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }
}
