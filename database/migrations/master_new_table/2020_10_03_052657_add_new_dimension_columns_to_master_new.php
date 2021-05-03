<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewDimensionColumnsToMasterNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_new', function (Blueprint $table) {
            $table->float('dim_length')->after('product_dimension')->nullable();
            $table->float('dim_width')->after('dim_length')->nullable();
            $table->float('dim_height')->after('dim_width')->nullable();
            $table->float('dim_diameter')->after('dim_height')->nullable();
            $table->float('dim_depth')->after('dim_diameter')->nullable();
            $table->float('dim_square')->after('dim_depth')->nullable();
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
            $table->dropColumn('dim_length');
            $table->dropColumn('dim_width');
            $table->dropColumn('dim_height');
            $table->dropColumn('dim_diameter');
            $table->dropColumn('dim_depth');
            $table->dropColumn('dim_square');
        });
    }
}
