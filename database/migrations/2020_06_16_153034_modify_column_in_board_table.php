<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyColumnInBoardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('board', function (Blueprint $table) {
            $table->renameColumn('is_private', 'is_published');
        });
        
        Schema::table('board', function (Blueprint $table) {
            $table->boolean('is_published')->default(0)->change();
        });
        
        DB::statement('UPDATE `board` SET `is_published` = !`is_published`');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {        
        Schema::table('board', function (Blueprint $table) {
            $table->renameColumn('is_published', 'is_private');
        });
        
        Schema::table('board', function (Blueprint $table) {
            $table->boolean('is_private')->default(1)->change();
        });
        
        DB::statement('UPDATE `board` SET `is_private` = !`is_private`');
    }
}
