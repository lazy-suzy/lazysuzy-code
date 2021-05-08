<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoardTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board', function (Blueprint $table) {
            $table->increments('board_id');
            $table->integer('user_id')->unsigned();
            $table->string('uuid', 50);
            $table->json('state')->nullable();
            $table->string('title')->nullable();
            $table->text('preview')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
        Schema::create('asset', function (Blueprint $table) {
            $table->increments('asset_id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->nullable();
            $table->string('price')->nullable();
            $table->string('brand')->nullable();
            $table->string('path');
            $table->string('transparent_path')->nullable();
            $table->boolean('is_private')->default(1);
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('board');
        Schema::dropIfExists('asset');
    }
}
