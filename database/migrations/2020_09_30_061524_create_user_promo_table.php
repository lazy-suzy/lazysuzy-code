<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPromoTable extends Migration
{
    public function up()
    {
        Schema::create('lz_promo_users', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->integer('user_id')->unsigned();
            $table->string('promo_code');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('promo_code')->references('code')->on('lz_promo')->onDelete('cascade');
            $table->index(['user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('lz_promo_users');
    }
}
