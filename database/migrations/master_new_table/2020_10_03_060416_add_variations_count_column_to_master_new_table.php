<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVariationsCountColumnToMasterNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_new', function (Blueprint $table) {
            $table->integer('serial')->after('id')->nullable();
            $table->integer('variations_count')->after('manual_adj')->nullable();
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
            $table->dropColumn('variations_count');
            $table->dropColumn('serial');
        });
    }
}
