<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateTableProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('product_sku')->nullable();
            $table->string('product_name')->nullable();
            $table->string('sku_hash')->nullable();
            $table->string('model_code')->nullable();
            $table->string('product_url')->nullable();
            $table->string('model_name')->nullable();
            $table->longText('images')->nullable();

            $table->text('thumb')->nullable();
            $table->text('product_dimension')->nullable();
            $table->string('color')->nullable();
            $table->string('min_price')->nullable();
            $table->string('max_price')->nullable();
            $table->string('price')->nullable();
            $table->string('mrp')->nullable();
            $table->string('parent_category')->nullable();
            $table->string('cat_name_long')->nullable();
            $table->string('department')->nullable();
            $table->text('product_feature')->nullable();
            $table->string('collection')->nullable();
            $table->string('product_set')->nullable();
            $table->string('product_condition')->nullable();
            $table->string('product_description')->nullable();
            $table->boolean('product_status')->nullable();
            $table->enum('level', ['active', 'inactive'])->nullable();
            $table->boolean('is_moved')->nullable();
            $table->boolean('update_status')->nullable();
            $table->longText('product_images')->nullable();
            $table->string('main_product_image')->nullable();
            $table->string('site_name')->nullable();
            $table->string('reviews')->nullable();
            $table->string('ratings')->nullable();
            $table->string('master_id')->nullable();
            $table->string('ls_id')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
