<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StyleFilter extends Model
{
    /**
     * Apply style filter on the product listing API
     *
     * @param [type] $query
     * @param [type] $all_filters
     * @return DBQueryIntance
     */
    public static function apply($query, $all_filters) {

        
        if (!isset($all_filters['style']) || sizeof($all_filters['style']) == 0)
            return $query;

        $query = $query->whereRaw('style REGEXP "' . implode("|", $all_filters['style']) . '"');
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
    public static function get_filter_data($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending) {

        $all_styles = [];

        // get distinct possible values for style filter
        $rows = DB::table("master_data")->whereRaw('style IS NOT NULL')
        ->whereRaw("LENGTH(style) > 0")
        ->where('product_status','active')
        ->distinct()
            ->get(['style']);
        $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);
        $products = DB::table("master_data")
        ->selectRaw("count(product_name) AS products, style")
        ->whereRaw('style IS NOT NULL')
        ->where('product_status','active')
        ->whereRaw('LENGTH(style) > 0');

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
            $products = MFDCountry::apply($products, $all_filters);
            $products = FabricFilter::apply($products, $all_filters);

        }

        $products = $products->groupBy('style')->get();

        // style data can contain comma separated values
        foreach ($rows as $row) {

            $filter_key = strtolower($row->style);
            $filter_keys = explode(",", $filter_key);

            foreach ($filter_keys as $key) {
                $all_styles[$key] = [
                    'name' => ucwords(trim($key)),
                    'value' => trim($key),
                    'count' => 0,
                    'enabled' => false,
                    'checked' => false
                ];
            }
        }

        foreach ($products as $b) {
            $filter_key = strtolower($b->style);
            $filter_keys = explode(",", $filter_key);
            foreach ($filter_keys as $key) {

                if (isset($all_styles[$key])) {

                    $all_styles[$key]["enabled"] = true;
                    if (isset($all_filters['style'])) {
                        $filter_key = $key;
                        if (in_array($filter_key, $all_filters['style'])) {
                            $all_styles[$key]["checked"] = true;
                        }
                    }

                    $all_styles[$key]["count"] += $b->products;
                }
            }
        }

        $style_holder = [];

        foreach ($all_styles as $name => $value) {
            array_push($style_holder, $value);
        }
        return $style_holder;
    }
}