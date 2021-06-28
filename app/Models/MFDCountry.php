<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MFDCountry extends Model
{
    /**
     * Apply mfg_country filter on the product listing API
     *
     * @param [type] $query
     * @param [type] $all_filters
     * @return DBQueryIntance
     */
    public static function apply($query, $all_filters)
    {


        if (!isset($all_filters['mfg_country']) || sizeof($all_filters['mfg_country']) == 0)
            return $query;

        $query = $query->whereRaw('mfg_country REGEXP "' . implode("|", $all_filters['mfg_country']) . '"');
        return $query;
    }

    /**
     * Send filter data that will be shown on the front end.
     * Options for user to select from.
     *
     * @param [type] $dept
     * @param [type] $cat
     * @param [type] $all_filters
     * @return array
     */
    public static function get_filter_data($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only,$handmade_products_only,$sustainable_products_only)
    {

        $all_mfg_countries = [];

        // get distinct possible values for mfg_country filter
        $rows = DB::table("master_data")->whereRaw('mfg_country IS NOT NULL')
        ->whereRaw("LENGTH(mfg_country) > 0")
        ->where('product_status','active')
        ->distinct()
            ->get(['mfg_country']);
        $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);
        $products = DB::table("master_data")
        ->selectRaw("count(product_name) AS products, mfg_country")
        ->whereRaw('mfg_country IS NOT NULL')
        ->where('product_status','active')
        ->whereRaw('LENGTH(mfg_country) > 0');

         // for getting new products
         if ($new_products_only == true) {
            $date_four_weeks_ago = date('Y-m-d', strtotime('-56 days'));
            $products = $products->whereRaw("created_date >= '" . $date_four_weeks_ago . "'");
            $products = $products->orderBy('new_group', 'asc');
        }

        // for getting products on sale
        if ($sale_products_only == true) {

            $products = $products->whereRaw('min_price >  0')
                ->whereRaw('min_was_price > 0')
                ->whereRaw('(convert(min_was_price, unsigned) > convert(min_price, unsigned) OR convert(max_was_price, unsigned) > convert(max_price, unsigned))')
                ->orderBy('serial', 'asc'); 
        }

         // for getting products on is spacesaver
         if ($spacesaver_products_only == true) {

            $products = $products->whereRaw('is_space_saver = "1"')
             ->orderBy('serial', 'asc'); 
        }
        
        // for getting products on is handmade
        if ($handmade_products_only == true) {

            $products = $products->whereRaw('is_handmade = "1"')
             ->orderBy('serial', 'asc'); 
        } 

        // for getting products on is sustainable
        if ($sustainable_products_only == true) {

            $products = $products->whereRaw('is_sustainable = "1"')
             ->orderBy('serial', 'asc'); 
        }

         // Added for trending products
         if (isset($trending)) {
            $products = $products->join("master_trending", "master_data.product_sku", "=", "master_trending.product_sku");
            $products = $products->whereRaw("master_trending.trend_score>=20");
            $products = $products->orderBy("master_trending.trend_score", "DESC");
        }

        if (sizeof($all_filters) != 0) {
            if (isset($all_filters['type']) && strlen($all_filters['type'][0]) > 0) {
                $LS_IDs = Product::get_sub_cat_LS_IDs($dept, $cat, $all_filters['type']);
            }


            // for /all API catgeory-wise filter
            if (
                isset($all_filters['category'])
                && !empty($all_filters['category'])
                && strlen($all_filters['category'][0])
            ) {
                // we want to show all the products of this category
                // so we'll have to get the sub-categories included in this 
                // catgeory
                $LS_IDs = SubCategory::get_sub_cat_LSIDs($all_filters['category']);
            }

            $products = $products->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');

            if (
                isset($all_filters['seating'])
                && isset($all_filters['seating'][0])
            ) {
                $products = $products
                    ->whereRaw('seating REGEXP "' . implode("|", $all_filters['seating']) . '"');
            }

            if (
                isset($all_filters['brand'])
                && strlen($all_filters['brand'][0]) > 0
            ) {
                $products = $products->whereIn('site_name', $all_filters['brand']);
            }

            // 2. price_from
            if (isset($all_filters['price_from'])) {
                $products = $products
                    ->whereRaw('min_price >= ' . $all_filters['price_from'][0] . '');
            }

            // 3. price_to
            if (isset($all_filters['price_to'])) {
                $products = $products
                    ->whereRaw('max_price <= ' . $all_filters['price_to'][0] . '');
            }

            if (
                isset($all_filters['color'])
                && strlen($all_filters['color'][0]) > 0
            ) {
                $products = $products
                    ->whereRaw('color REGEXP "' . implode("|", $all_filters['color']) . '"');
                // input in form - color1|color2|color3
            }

            $products = DimensionsFilter::apply($products, $all_filters);
            $products = CollectionFilter::apply($products, $all_filters);
            $products = MaterialFilter::apply($products, $all_filters);
            $products = DesignerFilter::apply($products, $all_filters);
            $products = StyleFilter::apply($products, $all_filters);
        }

        $products = $products->groupBy('mfg_country')->get();

        // mfg_country data can contain comma separated values
        foreach ($rows as $row) {

            $filter_key = strtolower($row->mfg_country);
            $filter_keys = explode(",", $filter_key);

            foreach ($filter_keys as $key) {
                $all_mfg_countries[$key] = [
                    'name' => in_array(trim($key), Config::get('meta.S_COUNTRIES')) ? strtoupper(trim($key)) : ucwords(trim($key)),
                    'value' => trim($key),
                    'count' => 0,
                    'enabled' => false,
                    'checked' => false
                ];
            }
        }

        foreach ($products as $b) {
            $filter_key = strtolower($b->mfg_country);
            $filter_keys = explode(",", $filter_key);

            foreach ($filter_keys as $key) {

                if (isset($all_mfg_countries[$key])) {

                    $all_mfg_countries[$key]["enabled"] = true;
                    $f_key = $key;

                    if (isset($all_filters['mfg_country'])) {
                        if (in_array($f_key, $all_filters['mfg_country'])) {
                            $all_mfg_countries[$f_key]["checked"] = true;
                        }
                    }

                    $all_mfg_countries[$f_key]["count"] += $b->products;
                }
            }
        }

        $mfg_country_holder = [];

        foreach ($all_mfg_countries as $name => $value) {
            array_push($mfg_country_holder, $value);
        }
        return $mfg_country_holder;
    }
}