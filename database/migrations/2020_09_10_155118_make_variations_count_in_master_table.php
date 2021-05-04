<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeVariationsCountInMasterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_data', function (Blueprint $table) {
            $table->integer('variations_count')->default(NULL); //New Arrivals Group Column with default value 3
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('master_data', function (Blueprint $table) {
            $table->dropColumn('variations_count');

        });
    }
}
