<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMfgCountryAndDesignerColumnToMasterData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_data', function (Blueprint $table) {
            $table->string('mfg_country')->nullable();
            $table->string('designer')->nullable();
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
            $table->dropColumn('mfg_country');
            $table->dropColumn('designer');
        });
    }
}
