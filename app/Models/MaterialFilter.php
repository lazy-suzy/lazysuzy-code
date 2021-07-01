<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PhpParser\ErrorHandler\Collecting;

class MaterialFilter extends Model
{
    
    /**
     * Apply material filter on the product listing API
     *
     * @param [type] $query
     * @param [type] $all_filters
     * @return DBQueryIntance
     */
    public static function apply($query, $all_filters) {

        if(!isset($all_filters['material']) || sizeof($all_filters['material']) == 0)
            return $query;

        $query = $query->whereRaw('material REGEXP "' . implode("|", $all_filters['material']) . '"');
        return $query;
    }


    /**
     * Send filter data that will be shown on the front end.
     * Options for user to select from.
     *
     * @param [type] $dept
     * @param [type] $cat
     * @param [type] $all_filters
     * @return Array
     */
    public static function get_filter_data($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only,$handmade_products_only,$sustainable_products_only) {

        $all_materials = [];

        // get distinct possible values for material filter
        $rows = DB::table("master_data")->whereRaw('material IS NOT NULL')
            ->whereRaw("LENGTH(material) > 0")
            ->where('product_status','active')
            ->distinct()
            ->get(['material']);
        $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);
        $products = DB::table("master_data")
        ->selectRaw("count(product_name) AS products, material")
        ->whereRaw('material IS NOT NULL')
        ->where('product_status','active')
        ->whereRaw('LENGTH(material) > 0');

         // for getting new products
         if ($new_products_only == true) {
            $date_four_weeks_ago = date('Y-m-d', strtotime('-56 days'));
            $products = $products->whereRaw("created_date >= '" . $date_four_weeks_ago . "'");
            $products = $products->orderBy('new_group', 'asc');
        }

        // for getting products on sale
        if ($sale_products_only == true) {

             // For 'Type' as parameter for sale products only
             if($all_filters['sale_type'] == 'sale'){
                $products = $products->where('promo_type','sale');
            }
            else if($all_filters['sale_type'] == 'clearance'){
                $products = $products->where('promo_type','clearance');
            }
            
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
            $products = FabricFilter::apply($products, $all_filters);
            $products = MFDCountry::apply($products, $all_filters);
            $products = DesignerFilter::apply($products, $all_filters);
            $products = StyleFilter::apply($products, $all_filters);

        }

        $products = $products->groupBy('material')->get();

        // material data can contain comma separated values
        foreach ($rows as $row) {

            $filter_key = strtolower($row->material);
            $filter_keys = explode(",", $filter_key);

            foreach($filter_keys as $key) {
                $all_materials[$key] = [
                    'name' => trim(ucwords($key)),
                    'value' => trim($key),
                    'count' => 0,
                    'enabled' => false,
                    'checked' => false
                ];
            }
            
        }

        foreach ($products as $b) {
            $filter_key = strtolower($b->material);
            $filter_keys = explode(",", $filter_key);
            foreach($filter_keys as $key) {

                if (isset($all_materials[$key])) {

                    $all_materials[$key]["enabled"] = true;
                    if (isset($all_filters['material'])) {
                        $filter_key = $key;
                        if (in_array($filter_key, $all_filters['material'])) {
                            $all_materials[$filter_key]["checked"] = true;
                        }
                    }

                    $all_materials[$key]["count"] += $b->products;
                }
              
            }
        }

        $material_holder = [];

        foreach ($all_materials as $name => $value) {
            array_push($material_holder, $value);
        }
        return $material_holder;
    }
}
