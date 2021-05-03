<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFilterColumnsToMasterNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_new', function (Blueprint $table) {
            $table->string('material')->after('shape')->nullable();
            $table->string('fabric')->after('material')->nullable();
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
            $table->dropColumn('material');
            $table->dropColumn('fabric');
        });
    }
}
