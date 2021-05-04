<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateNewProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_new', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_sku')->nullable();
            $table->string('sku_hash')->nullable();
            $table->enum('product_status', ['active', 'inactive'])->nullable();
            $table->string('model_code')->nullable();
            $table->string('product_url')->nullable();
            $table->string('model_name')->nullable();
            $table->longText('images')->nullable();
            $table->text('thumb')->nullable();
            $table->text('product_dimension')->nullable();
            $table->string('color')->nullable();
            $table->string('seating')->nullable();
            $table->string('shape')->nullable();
            $table->string('price')->nullable();
            $table->string('min_price')->nullable();
            $table->string('max_price')->nullable();
            $table->string('was_price')->nullable();
            $table->string('product_name')->nullable();
            $table->text('product_feature')->nullable();
            $table->string('collection')->nullable();
            $table->string('product_set')->nullable();
            $table->string('product_condition')->nullable();
            $table->string('product_description')->nullable();
            $table->timestamp('created_date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_date')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->longText('product_images')->nullable();
            $table->string('main_product_images')->nullable();
            $table->string('site_name');
            $table->string('brand');
            $table->string('reviews')->nullable();
            $table->string('rating')->nullable();
            $table->string('master_id')->nullable();
            $table->string('ls_id')->nullable();
            $table->integer('popularity')->nullable();
            $table->longText('product_name_ES')->nullable();
            $table->longText('image_xbg')->nullable();
            $table->longText('image_xbg_select_primary')->nullable();
            $table->longText('image_xbg_select_secondary')->nullable();
            $table->longText('image_xbg_cropped')->nullable();
            $table->longText('image_xbg_thumb')->nullable();
            $table->boolean('image_xbg_processed')->default(0);
            $table->integer('rec_order')->default(0);
            $table->integer('manual_adj')->default(0);
            $table->integer('set_id')->nullable();
            $table->enum('status', ['new', 'approved', 'rejected'])->default('new');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_new');
    }
}
