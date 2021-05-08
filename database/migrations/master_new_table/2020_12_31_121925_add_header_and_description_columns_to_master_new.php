<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHeaderAndDescriptionColumnsToMasterNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_new', function (Blueprint $table) {
            $table->string('product_sub_header_1')->nullable();
            $table->text('product_sub_desc_1')->nullable();
            $table->string('product_image_sub_1')->nullable();
            $table->string('product_sub_header_2')->nullable();
            $table->text('product_sub_desc_2')->nullable();
            $table->string('product_image_sub_2')->nullable();
            $table->string('product_sub_header_3')->nullable();
            $table->text('product_sub_desc_3')->nullable();
            $table->string('product_image_sub_3')->nullable();
            $table->string('product_sub_header_4')->nullable();
            $table->text('product_sub_desc_4')->nullable();
            $table->string('product_image_sub_4')->nullable();
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
            $table->dropColumn('product_sub_header_1');
            $table->dropColumn('product_sub_desc_1');
            $table->dropColumn('product_image_sub_1');
            $table->dropColumn('product_sub_header_2');
            $table->dropColumn('product_sub_desc_2');
            $table->dropColumn('product_image_sub_2');
            $table->dropColumn('product_sub_header_3');
            $table->dropColumn('product_sub_desc_3');
            $table->dropColumn('product_image_sub_3');
            $table->dropColumn('product_sub_header_4');
            $table->dropColumn('product_sub_desc_4');
            $table->dropColumn('product_image_sub_4');
        });
    }
}
