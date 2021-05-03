<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToBoardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('board', function (Blueprint $table) {
          $table->integer('type_room')->nullable()->after('preview');
          $table->integer('type_style')->nullable()->after('type_room');
          $table->integer('type_privacy')->default(0)->after('type_style');
          $table->boolean('is_private')->default(1)->after('type_privacy');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('board', function (Blueprint $table) {
            $table->dropColumn('type_room');
            $table->dropColumn('type_style');
            $table->dropColumn('type_privacy');
            $table->dropColumn('is_private');
        });
    }
}
