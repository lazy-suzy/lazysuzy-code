<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserVisits extends Model
{
    protected $table = "user_views";
    protected $fillable = ['product_sku', 'user_id', 'num_views', 'created_at', 'updated_at'];
    public static function save_user_visit_sku($user_id, $sku) {
        // Like the firstOrCreate method, updateOrCreate persists the model, so there's no need to call save():
        $visit = UserVisits::updateOrCreate(
            ['product_sku' => $sku, 'user_id' => $user_id],
            ['updated_at' => time(), 'num_views' => DB::raw('num_views + 1')]
        );

        return $visit;
    }

    public static function reset_visits($user_id) {
        $visit = UserVisits::updateOrCreate(
            ['user_id' => $user_id],
            ['updated_at' => time(), 'num_views' => 0]
        );

        return $visit;
    }

    public static function get_visited_skus($user_id) {

        $visits = UserVisits::where('user_id', $user_id)->get();
    }
}
