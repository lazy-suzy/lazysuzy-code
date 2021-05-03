<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDetailColumnsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('description')->nullable();
            $table->string('tag_line')->nullable();
            $table->string('username')->nullable()->unique();;
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('tag_line');
            $table->dropColumn('username');
            $table->dropColumn('website');
            $table->dropColumn('location');
        });
    }
}
