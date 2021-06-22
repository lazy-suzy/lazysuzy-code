<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DimensionsFilter extends Model
{
    protected $table = "master_data";

    public static function get_filter($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only) {

        // get min and max values for all the dimensions related properties.
        // based on the selected filters
        $dim_filters = [];
        $dim_columns = Config::get('tables.dimension_columns');
        $dim_label_map = Config::get('tables.dimension_labels');
        $products = DB::table((new self)->table)->select($dim_columns)
            ->where('product_status', 'active');

        // get applicable LS_IDs
         $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);

       // for getting new products
       if ($new_products_only == true) {
            $date_four_weeks_ago = date('Y-m-d', strtotime('-56 days'));
            $products = $products->whereRaw("created_date >= '" . $date_four_weeks_ago . "'");
            $products = $products->orderBy('new_group', 'asc');
            $products = $products->orderBy('created_date', 'desc');
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

        // Added for trending products
        if (isset($trending)) {
            $products = $products->join("master_trending", "master_data.product_sku", "=", "master_trending.product_sku");
            $products = $products->whereRaw("master_trending.trend_score>=20 and master_trending.is_active='1'");
            $products = $products->orderBy("master_trending.trend_score", "DESC");
        }
        
       if (sizeof($all_filters) != 0) {

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

            if (isset($all_filters['type']) && strlen($all_filters['type'][0]) > 0) {
                $LS_IDs = Product::get_sub_cat_LS_IDs($dept, $cat, $all_filters['type']);
            }

            // can avoid this matching because all products will by default 
            // require all products in DB
            if($dept != "all")
                $products = $products->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');

            if (
                isset($all_filters['color'])
                && strlen($all_filters['color'][0]) > 0
            ) {
                $products = $products
                    ->whereRaw('color REGEXP "' . implode("|", $all_filters['color']) . '"');
                // input in form - color1|color2|color3
            }

            if (
                isset($all_filters['seating'])
                && isset($all_filters['seating'][0])
            ) {
                $products = $products
                    ->whereRaw('seating REGEXP "' . implode("|", $all_filters['seating']) . '"');
            }

            if (
                isset($all_filters['shape'])
                && isset($all_filters['shape'][0])
            ) {
                $products = $products
                    ->whereRaw('shape REGEXP "' . implode("|", $all_filters['shape']) . '"');
            }
            if(isset($all_filters['price_from']) && isset($all_filters['price_to'])){
                $products = $products
                        ->whereRaw('((min_price between '. $all_filters['price_from'][0] .' and '.$all_filters['price_to'][0].') or (max_price between '.$all_filters['price_from'][0].' and '.$all_filters['price_to'][0].'))');

            }
            else{
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
            }

            if (
                isset($all_filters['brand'])
                && strlen($all_filters['brand'][0]) > 0
            ) {
                $products = $products->whereIn('brand', $all_filters['brand']);
            }

        }

        $products = CollectionFilter::apply($products, $all_filters);
        $products = MaterialFilter::apply($products, $all_filters);
        $products = DesignerFilter::apply($products, $all_filters);
        $products = FabricFilter::apply($products, $all_filters);
        $products = MFDCountry::apply($products, $all_filters);
        $products = StyleFilter::apply($products, $all_filters);
        $products_mod = $products; 
        $i=0;
        // get all min and max values for all dimensions columns
        foreach($dim_columns as $column) {
            //$products = $products->where($column, '>', 0);
            $dim_filters[$column] = [
                'label' => $dim_label_map[$column],
                'value' => $column,
                'min' => $products->min($column),
                'max' => $products->max($column)
            ];
            $bq = str_replace("dim_",'',$column); 
            if(isset($all_filters[strtolower($bq) . "_to"])) {
                $from = $all_filters[strtolower($bq) . "_from"][0];
                $to = $all_filters[strtolower($bq) . "_to"][0];
                if ($i == 0) {
                    $products_mod = $products->whereraw('('.$column.'>='.$from.' and '.$column.'<='. $to .')');
                } else {
                    $products_mod = $products_mod->whereraw('('.$column.'>='.$from.' and '.$column.'<='. $to .')');
                } 
                $i++;
                
            }
            else{
                        $dim_filters[$column] = [
                        'label' => $dim_label_map[$column],
                        'value' => $column,
                        'min' => $products_mod->min($column),
                        'max' => $products_mod->max($column)
                    ];
            }
        }//return $products_mod->tosql();

        return self::make_list_options($dim_filters, $all_filters);
    }

    /**
     * Input: min and max values for each type of dimensions filter
     * Output: list of ranges to select from, lower range and upper range will 
     * have a difference of env('meta.dimension_range_difference')
     *
     * @param [Associative Array] $dim_filters
     * @return [Associative Array] $dim_range_list: List of options, range based
     */
    private static function make_list_options($dim_filters, $all_filters) {

        $dim_range_list = [];
        foreach($dim_filters as $dimension_type => $obj) { 
            $min = $from = (float)$obj['min'];
            $max = $to = (float)$obj['max'];

            if(isset($all_filters[strtolower($obj['label']) . '_to'])) {
                $to =  (float)$all_filters[strtolower($obj['label']) . '_to'][0]; // $to = array of values
                $from =  (float)$all_filters[strtolower($obj['label']) . '_from'][0]; // from = array of values
            }  
            $dim_range_list[$dimension_type] = [
                'name' => $obj['label'],
                'key' => $obj['value'],
                'enabled' => true,
                'min' =>  isset($min) ? $min : 0 ,
                'max' =>  isset($max) ? $max : 0,
                "from" => isset($from) ? $from : 0,
                "to" => isset($to) ? $to : 0,
                "unit" =>  $obj['value']=='dim_weight' ? 'lbs' : 'inches'
            ];
        }

        return $dim_range_list;
    }

    private static function make_range($lower_bound, $upper_bound) {
        
        if(!isset($lower_bound) || !isset($upper_bound))
            return [];

        // round lower and upper limit to generate asthetic ranges 
        // like 2.5 to 34 will be converted to 0 to 40
      //  $lower_bound = floor((float) $lower_bound / 10) * 10;
        //$upper_bound = ceil((float) ($upper_bound / 10) + 0.1) * 10;
        $lower_bound_round = floor((float) $lower_bound / 10) * 10;
        $upper_bound_round = ceil((float) ($upper_bound / 10) + 0.1) * 10;

        $ranges = [];
        $dimension_range_difference = Config::get('meta.dimension_range_difference');
        
        $local_upper_bound = $lower_bound;
        $ranges[] = [
            "min" => $lower_bound,
            "max" => $upper_bound,
            "from" => round($lower_bound),
            "to" => round($upper_bound),
            "checked" => false
        ];
       /* while($lower_bound < $upper_bound) {
            $ranges[] = [
                "min" => $lower_bound,
                "max" => $local_upper_bound + $dimension_range_difference,
                "checked" => false
            ];
            $lower_bound += $dimension_range_difference;
            $local_upper_bound += $dimension_range_difference;
        }*/

        return $ranges;
    }

    public static function apply($query, $all_filters) {

        // get filter_key => column_name mapping 
        $col_to_label_map = Config::get('tables.dimension_labels'); 
        $label_to_col_map = array_flip($col_to_label_map);

        foreach($label_to_col_map as $label => $col_name) {
            if(isset($all_filters[strtolower($label) . "_to"])) {
                // this filer is present in the API

                $filter_to = $all_filters[strtolower($label) . "_to"];
                $filter_from = $all_filters[strtolower($label) . "_from"];

                $query = $query->where(function($query) use ($filter_from, 
                    $filter_to, $col_name) {
                    for ($i = 0; $i < sizeof($filter_to); $i++) {

                        if ($i == 0) {
                            $query = $query->where($col_name, ">=", $filter_from[$i])
                            ->where($col_name, "<=", $filter_to[$i]);
                        } else {
                            $query = $query->orWhere($col_name, ">=", $filter_from[$i])
                            ->where($col_name, "<=", $filter_to[$i]);
                        }
                    }
                });
            }
        }
        return $query;
    }
}