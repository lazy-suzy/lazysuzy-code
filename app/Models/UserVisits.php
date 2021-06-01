<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\AssignOp\Concat;

class UserVisits extends Model
{
    protected $table = "user_views";
    protected $fillable = ['product_sku', 'user_id', 'num_views', 'created_at', 'updated_at'];
    public static function save_user_visit_sku($user_id, $sku)
    {
        // Like the firstOrCreate method, updateOrCreate persists the model, so there's no need to call save():
        $visit = UserVisits::updateOrCreate(
            ['product_sku' => $sku, 'user_id' => $user_id],
            ['updated_at' => time(), 'num_views' => DB::raw('num_views + 1')]
        );

        return $visit;
    }

    public static function reset_visits($user_id)
    {
        $visit = UserVisits::updateOrCreate(
            ['user_id' => $user_id],
            ['updated_at' => time(), 'num_views' => 0]
        );

        return $visit;
    }

    public static function get_visited_skus($user_id)
    {

        $visits = UserVisits::select([
            Config::get('tables.master_table') . ".product_sku",
            Config::get('tables.master_table') . ".product_name",
            Config::get('tables.master_brands') . ".name as brand_name",
            Config::get('tables.master_table') . ".min_price as price",
            DB::raw("CONCAT('" . env('APP_URL') . "', " . Config::get('tables.master_table') . '.main_product_images' . ") AS image"),
           // Config::get('tables.master_table') . ".product_description",
          //  Config::get('tables.user_views') . ".num_views as visit_count",
          //  Config::get('tables.user_views') . ".updated_at as last_visit"
        ])->join(
            Config::get('tables.master_table'),
            Config::get('tables.user_views') . ".product_sku",
            "=",
            Config::get('tables.master_table') . ".product_sku"
        )->join(
            Config::get('tables.master_brands'),
            Config::get('tables.master_table') . ".brand",
            "=",
            Config::get('tables.master_brands') . ".value"
        )->where(Config::get('tables.user_views') . ".user_id", $user_id)
            ->orderBy('updated_at', 'DESC')->get();

        return $visits;
    }
}
