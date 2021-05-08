<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePromoCodesTable extends Migration
{
    public function up()
    {
        Schema::create('lz_promo', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->string('code');
            $table->string('name', 255);
            $table->string('description', 400);
            $table->enum('type',['pct','amt']);
            $table->integer('value')->default(0);
            $table->string('applicable_brands');
            $table->string('applicable_categories', 200)->default('*');
            $table->integer('allowed_count')->default('1');
            $table->string('special_users')->default('*');
            $table->datetime('expiry');
            $table->enum('is_active',['0','1'])->default('1');
            $table->index(['code']);

        });

        DB::unprepared(
            'Create trigger expiry_date_trigger BEFORE INSERT ON lz_promo for each row set NEW.expiry = TIMESTAMPADD(DAY, 14, NEW.expiry)'
        );
    }

    public function down()
    {
        Schema::dropIfExists('lz_promo');
    }
}