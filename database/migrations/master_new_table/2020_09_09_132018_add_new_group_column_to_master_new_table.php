<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewGroupColumnToMasterNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_new', function (Blueprint $table) {
            $table->integer('new_group')->default(3); //New Arrivals Group Column with default value 3
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
            $table->dropColumn('new_group');
        });
    }
}
