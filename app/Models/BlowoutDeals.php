<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class BlowoutDeals extends Model
{
    protected $table = "site_deals_flash";

    public static function get_deals()
    {
        $deals = BlowoutDeals::select([
            'product_sku',
            'parent_sku',
            'purchased_quantity',
            'total_quantity',
            'start_time',
            'end_time',
            DB::raw('NOW() as now')
        ])->get();

        $parent_skus = [];
        $parents = [];
        $variation_skus = [];
        $variations_parents = [];

        foreach ($deals as $deal) {
            $deal_sku = $deal->product_sku;
            $parent_sku = $deal->parent_sku;
            if ($deal->parent_sku == NULL) {
                $parent_skus[$deal_sku] = $deal;
                $parents[] = $deal_sku;
            } else {
                $variation_skus[$parent_sku] = $deal;
                $variations_parents[] = $parent_sku;
            }
        }

        // get parent SKU details
        $deals = self::get_deals_from_table();
        $parent_sku_details = $deals->get()->toArray();


        $var_query = self::get_var_deals_from_table();
        $variation_sku_details = $var_query->whereIn(Config::get('tables.master_table') . '.product_sku', $variations_parents)->get()->toArray();


        foreach ($variation_sku_details as &$deal) {
            $deal->is_variation = true;
            $deal->start_time = $variation_skus[$deal->product_sku]['start_time'];
            $deal->end_time = $variation_skus[$deal->product_sku]['end_time'];
            $deal->now = $variation_skus[$deal->product_sku]['now'];
            $deal->status = self::get_status($deal);
            $deal->time = self::get_time_remaining($deal, $deal->status);
        }

        foreach ($parent_sku_details as &$deal) {
            $deal['quantity'] = min($deal['quantity'], $deal['total_quantity']);
            $deal['status'] = self::get_status((object)$deal);
            $deal['time'] = self::get_time_remaining((object)$deal, $deal['status']);
            $deal['is_variation'] = false;
        }

        $deals = array_merge($parent_sku_details, $variation_sku_details);
        return $deals;
    }

    private static function get_time_remaining($deal, $status)
    {

        $start_time = strtotime($deal->start_time);
        $end_time = strtotime($deal->end_time);
        $now = strtotime($deal->now);

        if ($status == Config::get('meta.DEAL_ONGOING'))
            return $end_time - $now;
        else if ($status == Config::get('meta.DEAL_EXPIRED'))
            return 0;

        return $start_time - time();
    }

    private static function get_status($deal)
    {

        $quantity = $deal->quantity;
        $total_quantity = $deal->total_quantity;
        $start_time = strtotime($deal->start_time);
        $end_time = strtotime($deal->end_time);
        $now = strtotime($deal->now);

        if ($quantity >= $total_quantity || $now >= $end_time)
            return Config::get('meta.DEAL_EXPIRED');


        if ($now >= $start_time && $now < $end_time)
            return Config::get('meta.DEAL_ONGOING');

        return Config::get('meta.DEAL_INQUEUE');
    }

    private static function get_deals_from_table()
    {
        return BlowoutDeals::select(
            Config::get('tables.master_table') . '.product_name',
            Config::get('tables.master_table') . '.rating',
            Config::get('tables.master_table') . '.reviews',

            DB::raw("CONCAT('" . env('APP_URL') . "', " . Config::get('tables.master_table') . '.main_product_images' . ") AS image"),
            DB::raw('NOW() as now'),

            Config::get('tables.inventory') . '.price',
            Config::get('tables.inventory') . '.was_price',

            Config::get('tables.master_brands') . '.name as brand',

            Config::get('tables.blowout_deals') . '.product_sku',
            Config::get('tables.blowout_deals') . '.total_quantity',
            Config::get('tables.blowout_deals') . '.purchased_quantity as quantity',

            Config::get('tables.blowout_deals') . '.parent_sku',
            Config::get('tables.blowout_deals') . '.start_time',
            Config::get('tables.blowout_deals') . '.end_time'

        )->join(
            Config::get('tables.inventory'),
            Config::get('tables.inventory') . '.product_sku',
            "=",
            Config::get('tables.blowout_deals') . '.product_sku'
        )->join(
            Config::get('tables.master_table'),
            Config::get('tables.master_table') . '.product_sku',
            "=",
            Config::get('tables.blowout_deals') . '.product_sku'
        )->join(
            Config::get('tables.master_brands'),
            Config::get('tables.master_table') . '.brand',
            "=",
            Config::get('tables.master_brands') . '.value'
        )
            ->where(DB::raw(Config::get('tables.blowout_deals') . '.parent_sku', Config::get('tables.inventory') . '.parent_sku'))
            ->where(Config::get('tables.blowout_deals') . '.is_active', '1')
            ->where(Config::get('tables.blowout_deals') . '.parent_sku', NULL)
            ->orderBy(Config::get('tables.blowout_deals') . '.end_time', 'asc');
    }

    private static function get_var_deals_from_table()
    {
        return DB::table(Config::get('tables.master_table'))->select(
            [
                Config::get('tables.master_table') . '.product_sku',
                Config::get('tables.master_table') . '.price',
                Config::get('tables.master_table') . '.was_price',

                Config::get('tables.master_table') . '.product_name',
                Config::get('tables.master_table') . '.rating',
                Config::get('tables.master_table') . '.reviews',

                DB::raw("CONCAT('" . env('APP_URL') . "', " . Config::get('tables.master_table') . '.main_product_images' . ") AS image"),
                DB::raw('NOW() as now'),

                Config::get('tables.master_brands') . '.name as brand',

                Config::get('tables.blowout_deals') . '.total_quantity',
                Config::get('tables.blowout_deals') . '.purchased_quantity as quantity',

            ]
        )->join(
            Config::get('tables.blowout_deals'),
            Config::get('tables.blowout_deals') . '.parent_sku',
            '=',
            Config::get('tables.master_table') . '.product_sku'
        )->join(
            Config::get('tables.master_brands'),
            Config::get('tables.master_brand') . '.value',
            '=',
            Config::get('tables.master_table') . '.brand'
        );
    }
}
