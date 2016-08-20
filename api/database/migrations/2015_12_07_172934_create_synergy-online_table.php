<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSynergyOnlineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('infinity')->create('synergy-online', function ($table) {
            $table->bigIncrements('id');
            $table->bigInteger('lead_id');
            $table->string('title');
            $table->string('land_code');
            $table->string('phone');
            $table->integer('state');
            $table->string('manager');
            $table->integer('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('infinity')->drop('synergy-online');
    }
}
