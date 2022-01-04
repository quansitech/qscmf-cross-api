<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrossApiRegister extends Migration
{

    public function beforeCmmUp()
    {
        //
    }

    public function beforeCmmDown()
    {
        //
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qs_cross_api_register', function (Blueprint $table) {
            $table->string('id', 50);
            $table->string('name', 50);
            $table->string('sign', 50);
            $table->string('ip', 20);
            $table->string('api', 2000);
            $table->decimal('create_date', 14,4);
            $table->unique('sign', 'uq_sign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qs_cross_api_register');
    }

    public function afterCmmUp()
    {
        //
    }

    public function afterCmmDown()
    {
        //
    }
}
