<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class Trending extends Model
{
    protected $table = "master_trending";
    public static function get_trending()
    {
        $products = Trending::join(
            Config::get('tables.master_table'),
            Config::get('tables.master_table') . '.product_sku',
            '=',
            Config::get('tables.trending_products') . '.product_sku'
        )->join(
            Config::get('tables.master_brands'),
            Config::get('tables.master_brands') . '.value',
            '=',
            Config::get('master_table') . '.brand'
        )->where(Config::get('tables.trending_products') . '.is_active', '1')
            ->orderBy(Config::get('tables.trending_products') . '.score')->get();

        $trending_products = [];

        foreach ($products as $prod) {
            $trending_products[] = Product::get_details(
                $prod,
                null,
                true,
                Wishlist::is_wishlisted(Auth::user(), $prod->product_sku),
                false,
                true
            );
        }
        return $trending_products;
    }
}
