<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBackOrderColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_new', function (Blueprint $table) {
            $table->boolean('is_back_order')->nullable();
            $table->string('back_order_msg')->nullable();
            $table->string('back_order_msg_date')->nullable();
            $table->string('online_msg')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('master_new', function (Blueprint $table) {
            $table->dropColumn('is_back_order');
            $table->dropColumn('back_order_msg');
            $table->dropColumn('back_order_msg_date');
            $table->dropColumn('online_msg');
        });

    }
}
