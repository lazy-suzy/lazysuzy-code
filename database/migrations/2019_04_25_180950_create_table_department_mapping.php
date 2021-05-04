<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDepartmentMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('department_mapping', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ls_id');
            $table->string('department_name');
            $table->string('cat_name_long');
            $table->string('product_sub_category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('department_mapping', function (Blueprint $table) {
            //
        });
    }
}
