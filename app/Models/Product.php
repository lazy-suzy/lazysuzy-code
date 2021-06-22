<?php

namespace App\Models;

use App\Models\Collections;
use App\Http\Controllers\ProductController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Dimension;
use App\Models\Cart;

use Auth;
use Hamcrest\Util;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    protected $table = "master_data";

    //Guard Id from mass assignment
    protected $guarded = ['id'];

    // Timestamp Column names in DB
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    /* protected $casts = [
        'product_dimension' => 'array',
    ]; */
    public static $base_siteurl = 'https://www.lazysuzy.com';
    static $count = 0;
    private static $color_map = null;
    private static $cart_table = 'lz_user_cart';

    public static function trending_products($limit)
    {

        $trending_products = [];
        $rows = DB::table("trending_products")
            ->select("*")
            ->join("master_data", "master_data.product_sku", "=", "trending_products.product_sku")
            ->join("master_brands", "master_data.brand", "=", "master_brands.value")
            ->limit($limit)
            ->orderBy("trending_products.rank", "ASC")
            ->get();

        foreach ($rows as $product) {
            $variations = null; // Product::get_variations($product, null, false);
            array_push($trending_products, Product::get_details($product, $variations, true, false, true));
        }

        return $trending_products;
    }

    public static function get_LS_IDs($dept, $cat = null)
    {

        $LS_IDs = [];
        $data   = DB::table('mapping_core')
            ->select('LS_ID');

        // "all" is for getting all the products irrespective of any department or category
        if ($dept != "all") {
            if (null == $cat) {
                $data = $data
                    ->where('dept_name_url', $dept);
            } else {
                $data = $data
                    ->where('dept_name_url', $dept)
                    ->where('cat_name_url', $cat);
            }
        }

        $data = $data->get();

        foreach ($data as $key => $val) {
            array_push($LS_IDs, $val->LS_ID);
        }

        return $LS_IDs;
    }



    public static function get_sub_cat_LS_ID($dept, $cat, $sub_category)
    {
        $ls_id = DB::table('mapping_core')
            ->select('LS_ID');
        if ($dept != 'all')
            $ls_id = $ls_id->where('dept_name_url', $dept);
        if ($cat != null)
            $ls_id = $ls_id->where('cat_name_url', $cat);

        $ls_id = $ls_id->where('cat_sub_url', $sub_category)->get();

        /* echo json_encode(Utility::get_sql_raw($ls_id));
        die();  */

        if ($ls_id) {
            return $ls_id[0]->LS_ID;
        } else {
            return null;
        }
    }

    public static function get_sub_cat_LS_IDs($dept, $cat, $sub_categories)
    {
        $LS_IDs = [];
        foreach ($sub_categories as $sub_category) {
            $ls_id = Product::get_sub_cat_LS_ID($dept, $cat, $sub_category);
            if (null != $ls_id) {
                array_push($LS_IDs, $ls_id);
            }
        }

        return $LS_IDs;
    }

    public static function get_filter_products($dept, $cat = null, $subCat = null, $isAdmiAPICall = false)
    {
        $perPage = 24;
        $LS_IDs = null;
        $PRICE_ASC = "price_low_to_high";
        $PRICE_DESC = "price_high_to_low";
        $POPULARITY = "popularity";
        $RECOMMENDED = "recommended";

        $sort_type_filter = [
            [
                "name" => "Top Picks",
                "value" => $RECOMMENDED,
                "enabled" => false
            ],
            [
                "name" => "Popularity",
                "value" => $POPULARITY,
                "enabled" => false
            ],
            [
                "name" => "Price: Low to High",
                "value" => $PRICE_ASC,
                "enabled" => false
            ],
            [
                "name" => "Price: High to Low",
                "value" => $PRICE_DESC,
                "enabled" => false
            ]

        ];

        // getting all the extra params from URL to parse applied filters
        $page_num    = Input::get("pageno");
        $limit       = Input::get("limit");
        $sort_type   = Input::get("sort_type");
        $filters     = Input::get("filters");
        $trending     = Input::get("trending");
        $new_products_only  = filter_var(Input::get('new'), FILTER_VALIDATE_BOOLEAN);
        $sale_products_only = filter_var(Input::get('sale'), FILTER_VALIDATE_BOOLEAN);
        $spacesaver_products_only = filter_var(Input::get('spacesaver'), FILTER_VALIDATE_BOOLEAN); 
        $is_details_minimal = filter_var(Input::get('board-view'), FILTER_VALIDATE_BOOLEAN);
        $is_best_seller     = filter_var(Input::get('bestseller'), FILTER_VALIDATE_BOOLEAN);
        $is_admin_call = filter_var(Input::get('admin'), FILTER_VALIDATE_BOOLEAN);



        $all_filters = [];
        $query       = DB::table('master_data')->where('product_status', 'active');

        // Added for trending products
        if (isset($trending)) {
            $query = $query->join("master_trending", "master_data.product_sku", "=", "master_trending.product_sku");
            $query = $query->whereRaw("master_trending.trend_score>=20 and master_trending.is_active='1'");
        }


        if (isset($sort_type)) {
            for ($i = 0; $i < sizeof($sort_type_filter); $i++) {
                if ($sort_type_filter[$i]['value'] == $sort_type) {
                    $sort_type_filter[$i]['enabled'] = true;
                }
            }
        }

        $all_filters['sort_type'] = $sort_type_filter;
        if (!isset($limit)) {
            $limit = $perPage;
        }

        $start = $page_num * $limit;

        if (isset($filters)) {
            $filter_blocks = explode(";", $filters);
            foreach ($filter_blocks as $block) {
                $block_str = explode(":", $block);

                if (isset($block_str[0]) && isset($block_str[1])) {

                    // change filter keys for some filters 
                    $block_str[0] = ($block_str[0] == "country") ? "mfg_country" : $block_str[0];

                    $all_filters[$block_str[0]] = explode(",", $block_str[1]);
                    $all_filters[$block_str[0]] = array_map("strtolower", $all_filters[$block_str[0]]);
                }
            }

            // FILTERS
            // 1. brand_names
            if (
                isset($all_filters['brand'])
                && strlen($all_filters['brand'][0]) > 0
            ) {
                $query = $query->whereRaw('brand REGEXP "' . implode("|", $all_filters['brand']) . '"');
            }

            if(isset($all_filters['price_from']) && isset($all_filters['price_to'])){
                    $query = $query
                            ->whereRaw('((min_price between '. $all_filters['price_from'][0] .' and '.$all_filters['price_to'][0].') or (max_price between '.$all_filters['price_from'][0].' and '.$all_filters['price_to'][0].'))');

            }
            else{
                     // 2. price_from
                    if (isset($all_filters['price_from'])) {
                        $query = $query
                            ->whereRaw('min_price >= ' . $all_filters['price_from'][0] . '');
                    }

                    // 3. price_to
                    if (isset($all_filters['price_to'])) {
                        $query = $query
                            ->whereRaw('max_price <= ' . $all_filters['price_to'][0] . '');
                    }

            }
            
     
            if (
                isset($all_filters['color'])
                && strlen($all_filters['color'][0]) > 0
            ) {
                $query = $query
                    ->whereRaw('color REGEXP "' . implode("|", $all_filters['color']) . '"');
                // input in form - color1|color2|color3
            }

            if (
                isset($all_filters['seating'])
                && isset($all_filters['seating'][0])
            ) {
                $query = $query
                    ->whereRaw('seating REGEXP "' . implode("|", $all_filters['seating']) . '"');
            }

            if (
                isset($all_filters['shape'])
                && isset($all_filters['shape'][0])
            ) {
                $query = $query
                    ->whereRaw('shape REGEXP "' . implode("|", $all_filters['shape']) . '"');
            }
        }


        // only include sub category products if subcategory is not null
        if ($subCat != null) {
            $LS_IDs = [Product::get_sub_cat_LS_ID($dept, $cat, $subCat)];
        }

        // for /all API catgeory-wise filter
        if (
            isset($all_filters['category'])
            && strlen($all_filters['category'][0])
        ) {
            // we want to show all the products of this category
            // so we'll have to get the sub-categories included in this
            // catgeory
            $LS_IDs = SubCategory::get_sub_cat_LSIDs($all_filters['category']);
        }

        // 4. type
        // NOTE: This filter will always come after category filter
        if (isset($all_filters['type']) && strlen($all_filters['type'][0]) > 0) {
            // will only return products that match the LS_IDs for the `types` mentioned.
            $LS_IDs = Product::get_sub_cat_LS_IDs($dept, $cat, $all_filters['type']);
        } else if (!isset($all_filters['category'])) {
            // 5. departments and categories
            if (null != $cat) {
                $LS_IDs = Product::get_LS_IDs($dept, $cat);
            } else {
                $LS_IDs = Product::get_LS_IDs($dept);
            }
        }

        // override LS_ID array is there is a  `bestseller` filter applied
        if ($is_best_seller) {
            $LS_IDs = ['99'];
        }

        if (!isset($trending) && !$new_products_only && !$sale_products_only && !$spacesaver_products_only) {
            $query = $query->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');
        }
        else{
                if ($filters != '') {
                    $query = $query->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');
                } 
         }
        $query = DimensionsFilter::apply($query, $all_filters);
        $query = CollectionFilter::apply($query, $all_filters);
        $query = MaterialFilter::apply($query, $all_filters);
        $query = FabricFilter::apply($query, $all_filters);
        $query = DesignerFilter::apply($query, $all_filters);
        $query = MFDCountry::apply($query, $all_filters);
        $query = StyleFilter::apply($query, $all_filters);



        // 7. sort_type
        if (isset($sort_type) || (isset($all_filters['collection'])
            && sizeof($all_filters['collection']) > 0)) {

            if ($sort_type == $PRICE_ASC) {
                $query = $query->orderBy('min_price', 'asc');
            } else if ($sort_type == $PRICE_DESC) {
                $query = $query->orderBy('min_price', 'desc');
            } else if ($sort_type == $POPULARITY) {
                $query = $query->orderBy('popularity', 'desc');
            } else if ($sort_type == $RECOMMENDED) {
                $query = $query->orderBy('serial', 'asc');
            } else {
                $query = $query->orderBy('serial', 'asc');
            }
        }
        // set default sorting to popularity
        else {

            // Added for trending products
            if (isset($trending)) {
                $query = $query->orderBy("master_trending.trend_score", "DESC");
            } else {
                if ($sale_products_only == false && !$new_products_only)
                    $query = $query->orderBy('serial', 'asc');
            }
        }

        if ($is_details_minimal) {
            if (!$is_admin_call) {
                $all_filters['is_admin_call'] = false;
                $query = $query->whereRaw('image_xbg_processed = 1');
            } else {
                // for admin api calls xbg_image filter must not be applied
                $all_filters['is_admin_call'] = true;
            }
            $all_filters['is_board_view'] = true;
        } else {
            $all_filters['is_board_view'] = false;
        }

        // for new products only
        if ($new_products_only == true) {
            $date_four_weeks_ago = date('Y-m-d', strtotime('-56 days'));
            $query = $query->whereRaw("created_date >= '" . $date_four_weeks_ago . "'");
            $query = $query->orderBy('new_group', 'asc');
            $query = $query->orderBy('created_date', 'desc');
        }

        // for getting products on sale
        if ($sale_products_only == true) {

            $query = $query->whereRaw('min_price >  0')
                ->whereRaw('min_was_price > 0')
                ->whereRaw('(convert(min_was_price, unsigned) > convert(min_price, unsigned) OR convert(max_was_price, unsigned) > convert(max_price, unsigned))')
                ->orderBy('serial', 'asc');
            //->orderBy(DB::raw("`min_price` / `min_was_price`"), 'asc');
        }

          // for getting products on is spacesaver
          if ($spacesaver_products_only == true) {

            $query = $query->whereRaw('is_space_saver = "1"')
             ->orderBy('serial', 'asc'); 
        } 

        // 6. limit
        $all_filters['limit'] = $limit;
        $all_filters['count_all'] = $query->count();
        $query = $query->offset($start)->limit($limit);
        /* echo $query->toSql();
        print_r($query->getBindings());
        die(); */

        //echo "<pre>" . print_r($all_filters, ""true);
        $query = $query->join("master_brands", "master_data.brand", "=", "master_brands.value");
        $is_listing_API_call = true;


        if ($isAdmiAPICall == true) $is_listing_API_call = false;
        $a = Product::get_product_obj($query->get(), $all_filters, $dept, $cat, $subCat, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only, $is_listing_API_call, $is_details_minimal, $is_admin_call);

        // add debug params to test quickly
        $a['a'] = Utility::get_sql_raw($query);
        return $a;
    }

    public static function get_dept_cat_LS_ID_arr($dept, $cat)
    {
        $LS_IDs = null;

        if (null != $cat) {
            $LS_IDs = Product::get_LS_IDs($dept, $cat);
        } else {
            $LS_IDs = Product::get_LS_IDs($dept);
        }

        return $LS_IDs;
    }

    // this is only for /all API
    public static function get_all_dept_category_filter($brand_name = null, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only)
    {
        $in_filter_categories = $all_filters['category'];
        $LS_IDs = DB::table("master_data")
            ->select("LS_ID")
            ->where("product_status", "active")
            ->where("LS_ID", '!=', '');
        if ($brand_name !== null) $LS_IDs = $LS_IDs->where("brand", $brand_name);
        /* if ($sale_products_only) {
            $LS_IDs = $LS_IDs->whereRaw('min_price != min_was_price');
        }*/
        // for getting new products
        if ($new_products_only == true) {
            $date_four_weeks_ago = date('Y-m-d', strtotime('-56 days'));
            $LS_IDs = $LS_IDs->whereRaw("created_date >= '" . $date_four_weeks_ago . "'");
            $LS_IDs = $LS_IDs->orderBy('new_group', 'asc');
            $LS_IDs = $LS_IDs->orderBy('created_date', 'desc');
        }

        // for getting products on sale
        if ($sale_products_only == true) {

            $LS_IDs = $LS_IDs->whereRaw('min_price >  0')
                ->whereRaw('min_was_price > 0')
                ->whereRaw('(convert(min_was_price, unsigned) > convert(min_price, unsigned) OR convert(max_was_price, unsigned) > convert(max_price, unsigned))')
                ->orderBy('serial', 'asc'); 
        }

        // for getting products on is spacesaver
        if ($spacesaver_products_only == true) { 
            $LS_IDs = $LS_IDs->whereRaw('is_space_saver = "1"')
             ->orderBy('serial', 'asc'); 
        } 

        $LS_IDs = Filters::apply(null, null, $all_filters, $LS_IDs, Config::get('meta.FILTER_ESCAPE_CATGEORY'));
        if (sizeof($all_filters) != 0) {
         

            if (
                isset($all_filters['seating'])
                && isset($all_filters['seating'][0])
            ) {
                $LS_IDs = $LS_IDs
                    ->whereRaw('seating REGEXP "' . implode("|", $all_filters['seating']) . '"');
            }

            if (
                isset($all_filters['brand'])
                && strlen($all_filters['brand'][0]) > 0
            ) {
                $LS_IDs = $LS_IDs->whereIn('brand', $all_filters['brand']);
            }

            if(isset($all_filters['price_from']) && isset($all_filters['price_to'])){
                $LS_IDs = $LS_IDs
                        ->whereRaw('((min_price between '. $all_filters['price_from'][0] .' and '.$all_filters['price_to'][0].') or (max_price between '.$all_filters['price_from'][0].' and '.$all_filters['price_to'][0].'))');

            }
            else{
                    // 2. price_from
                    if (isset($all_filters['price_from'])) {
                        $LS_IDs = $LS_IDs
                            ->whereRaw('min_price >= ' . $all_filters['price_from'][0] . '');
                    }

                    // 3. price_to
                    if (isset($all_filters['price_to'])) {
                        $LS_IDs = $LS_IDs
                            ->whereRaw('max_price <= ' . $all_filters['price_to'][0] . '');
                    }
            }
            if (
                isset($all_filters['color'])
                && strlen($all_filters['color'][0]) > 0
            ) {
                $LS_IDs = $LS_IDs
                    ->whereRaw('color REGEXP "' . implode("|", $all_filters['color']) . '"');
                // input in form - color1|color2|color3
            }        
        }

        // all all new filters here
        //$LS_IDs = Filters::apply(null, null, $all_filters, $LS_IDs, Config::get('meta.FILTER_ESCAPE_CATEGORY'));

        $LS_IDs = $LS_IDs->distinct("LS_ID")
            ->get();

        // for collections filter, only show those catgeories that are available for 
        // the given collection values 
        // this will be empty if collections filter is not applied
        $collection_catgeory_LS_IDs = Collections::get_LSIDs($all_filters);

        /* // get product categories filters
         * @param bool $dept_name_url_api
         * @param bool $is_home_call
         * @param bool $is_board_view
        $departments = Department::get_all_departments(false, false, true);
        $categories = [];
        foreach ($departments['all_departments'] as $department) {
            if (isset($department['categories'])
                && sizeof($department['categories']) > 0) {
                foreach ($department['categories'] as $cat) {
                    $categories[$cat['LS_ID']] = [
                        'name' => $cat['filter_label'],
                        'value' => $cat['LS_ID'],
                        'checked' => false,
                        'enabled' => false
                    ];
                }
            }
        }
        */

        // if 'is_boad_view' is set to true this function will also check for sub-categories
        // otherwise will only get categories

        $categories = Category::get_board_categories($all_filters['is_board_view']);

        $filter_categories = [];
        $filter_categories1 = [];




        foreach ($LS_IDs as $LS_ID) {
            $IDs = explode(",", $LS_ID->LS_ID);
            foreach ($IDs as $ID) {
                $similar_LS_ID_arr = [];

                $get_dept_cat_url = DB::table("mapping_core")
                    ->select("dept_name_url", "cat_name_url")
                    ->where('LS_ID', '=', $ID)
                    ->get();

                if ($get_dept_cat_url != '[]') {
                    $get_similar_LS_ID = DB::table("mapping_core")
                        ->select("LS_ID")
                        ->where('dept_name_url', '=', $get_dept_cat_url[0]->dept_name_url)
                        ->where('cat_name_url', '=', $get_dept_cat_url[0]->cat_name_url)
                        ->get();

                    foreach ($get_similar_LS_ID as $Slsid) {
                        $mid = $Slsid->LS_ID;
                        if (isset($categories[$mid])) {
                            $categories[$mid]['enabled'] = true;
                            if (in_array($categories[$mid]['value'], $in_filter_categories)) {
                                $categories[$mid]['checked'] = true;
                            }
                            array_push($filter_categories, $categories[$mid]);
                            unset($categories[$mid]);
                        }
                    }
                }



                if ((empty($collection_catgeory_LS_IDs) && isset($categories[$ID]))
                    || (!empty($collection_catgeory_LS_IDs)
                        && in_array($ID, $collection_catgeory_LS_IDs)
                        && isset($categories[$ID]))
                ) {
                    if (in_array($categories[$ID]['value'], $in_filter_categories)) {
                        $categories[$ID]['checked'] = true;
                    }

                    $categories[$ID]['enabled'] = true;
                    array_push($filter_categories, $categories[$ID]);
                    unset($categories[$ID]);
                }
            }
        }

        foreach ($categories as $cat)
            array_push($filter_categories, $cat);
        // sort based on LS_ID
        usort($filter_categories, function ($cat1, $cat2) {
            if ($cat1['value'] == $cat2['value']) return 0;

            return $cat1['value'] < $cat2['value'] ? -1 : 1;
        });

        return $filter_categories;
    }

    public static function get_seating_filter($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only)
    {

        $all_seating = [];
        $seating_holder = [];

        $do_process = true;

        if ($dept == 'all') {
            if (!isset($all_filters['category']))
                $do_process = false;
            else if (sizeof($all_filters['category']) == 0)
                $do_process = false;
        }

        if ($do_process == true) {
            $rows = DB::table("filter_map_seating")->get();
            $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);
            $products = DB::table("master_data")
                ->selectRaw("count(product_name) AS products, seating")
                ->where("product_status", "active");

            /*if ($sale_products_only) {
                $products = $products->whereRaw('min_price != min_was_price');
            }*/
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

                $products = $products->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');
                $products = DimensionsFilter::apply($products, $all_filters);
                $products = CollectionFilter::apply($products, $all_filters);
                $products = MaterialFilter::apply($products, $all_filters);
                $products = FabricFilter::apply($products, $all_filters);
                $products = DesignerFilter::apply($products, $all_filters);
                $products = MFDCountry::apply($products, $all_filters);
                $products = StyleFilter::apply($products, $all_filters);


                if (
                    isset($all_filters['color'])
                    && strlen($all_filters['color'][0]) > 0
                ) {
                    $products = $products
                        ->whereRaw('color REGEXP "' . implode("|", $all_filters['color']) . '"');
                    // input in form - color1|color2|color3
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

            $products = $products->groupBy('seating')->get();
            foreach ($rows as $row) {
                $all_seating[$row->seating] = [
                    'name' => $row->seating,
                    'value' => $row->seating,
                    'count' => 0,
                    'enabled' => false,
                    'checked' => false
                ];
            }
            //return $products;
            foreach ($products as $b) {
                if (isset($all_seating[$b->seating])) {
                    $all_seating[$b->seating]["enabled"] = true;
                    if (isset($all_filters['seating'])) {
                        if (in_array($b->seating, $all_filters['seating'])) {
                            $all_seating[$b->seating]["checked"] = true;
                        }
                    }

                    $all_seating[$b->seating]["count"] = $b->products;
                }
            }



            foreach ($all_seating as $name => $value) {
                array_push($seating_holder, $value);
            }
        }
        return $seating_holder;
    }

    public static function get_shape_filter($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only)
    {

        $all_shapes = [];
        $shapes_holder = [];

        // for all products API
        // $dept will be 'all' and the catgeories will come from
        // $all_filters data, we want to show the type filter only when some
        // catgeory is selected, so return an empty array for types if
        // no categories is selected
        $do_process = true;

        if ($dept == 'all') {
            if (!isset($all_filters['category']))
                $do_process = false;
            else if (sizeof($all_filters['category']) == 0)
                $do_process = false;
        }

        if ($do_process == true) {

            $rows = DB::table("master_data")->whereRaw('shape IS NOT NULL')->where("product_status", "active")->whereRaw("LENGTH(shape) > 0")->distinct()->get(['shape']);
            $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);
            $products = DB::table("master_data")
            ->selectRaw("count(product_name) AS products, shape")
            ->where("product_status", "active");

            /*if ($sale_products_only) {
                $products = $products->whereRaw('min_price != min_was_price');
            }*/
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
                if (isset($all_filters['type']) && strlen($all_filters['type'][0]) > 0) {
                    $LS_IDs = Product::get_sub_cat_LS_IDs($dept, $cat, $all_filters['type']);
                }


                // for /all API catgeory-wise filter
                if (
                    isset($all_filters['category'])
                    && strlen($all_filters['category'][0])
                ) {
                    // we want to show all the products of this category
                    // so we'll have to get the sub-categories included in this
                    // catgeory
                    $LS_IDs = SubCategory::get_sub_cat_LSIDs($all_filters['category']);
                }

                $products = $products->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');
                $products = DimensionsFilter::apply($products, $all_filters);
                $products = CollectionFilter::apply($products, $all_filters);
                $products = MaterialFilter::apply($products, $all_filters);
                $products = FabricFilter::apply($products, $all_filters);
                $products = DesignerFilter::apply($products, $all_filters);
                $products = MFDCountry::apply($products, $all_filters);
                $products = StyleFilter::apply($products, $all_filters);


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
                    $products = $products->whereIn('brand', $all_filters['brand']);
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
                    isset($all_filters['color'])
                    && strlen($all_filters['color'][0]) > 0
                ) {
                    $products = $products
                        ->whereRaw('color REGEXP "' . implode("|", $all_filters['color']) . '"');
                    // input in form - color1|color2|color3
                }
            }
            $products = $products->groupBy('shape')->get();

            foreach ($rows as $row) {
                $all_shapes[$row->shape] = [
                    'name' => $row->shape,
                    'value' => strtolower($row->shape),
                    'count' => 0,
                    'enabled' => false,
                    'checked' => false
                ];
            }

            foreach ($products as $b) {
                if (isset($all_shapes[$b->shape])) {
                    $all_shapes[$b->shape]["enabled"] = true;
                    if (isset($all_filters['shape'])) {
                        if (in_array(strtolower($b->shape), $all_filters['shape'])) {
                            $all_shapes[$b->shape]["checked"] = true;
                        }
                    }

                    $all_shapes[$b->shape]["count"] = $b->products;
                }
            }



            foreach ($all_shapes as $name => $value) {
                array_push($shapes_holder, $value);
            }
        }
        return $shapes_holder;
    }

    public static function get_brands_filter($dept, $cat, $all_filters,  $sale_products_only, $new_products_only,$trending, $spacesaver_products_only)
    {
        $all_brands = [];
        $all_b = DB::table("master_brands")->orderBy("name")->get();
        $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);

        foreach ($all_b as $brand) {
            $all_brands[$brand->value] = [
                "name" => $brand->name,
                "value" => strtolower($brand->value),
                "enabled" => false,
                "checked" => false,
                "count" => 0
            ];
        }

        $product_brands = DB::table("master_data")
            ->selectRaw("count(product_name) AS products, brand")
            ->where("product_status", "active");
        /*if ($sale_products_only) {
            $product_brands = $product_brands->whereRaw('min_price != min_was_price');
        }*/
        // for getting new products
        if ($new_products_only == true) {
            $date_four_weeks_ago = date('Y-m-d', strtotime('-56 days'));
            $product_brands = $product_brands->whereRaw("created_date >= '" . $date_four_weeks_ago . "'");
            $product_brands = $product_brands->orderBy('new_group', 'asc');
            $product_brands = $product_brands->orderBy('created_date', 'desc');
        }

        // for getting products on sale
        if ($sale_products_only == true) {

            $product_brands = $product_brands->whereRaw('min_price >  0')
                ->whereRaw('min_was_price > 0')
                ->whereRaw('(convert(min_was_price, unsigned) > convert(min_price, unsigned) OR convert(max_was_price, unsigned) > convert(max_price, unsigned))')
                ->orderBy('serial', 'asc'); 
        }

        // for getting products on is spacesaver
        if ($spacesaver_products_only == true) { 
            $product_brands = $product_brands->whereRaw('is_space_saver = "1"')
             ->orderBy('serial', 'asc'); 
        }

        // Added for trending products
        if (isset($trending)) {
            $product_brands = $product_brands->join("master_trending", "master_data.product_sku", "=", "master_trending.product_sku");
            $product_brands = $product_brands->whereRaw("master_trending.trend_score>=20 and master_trending.is_active='1'");
            $product_brands = $product_brands->orderBy("master_trending.trend_score", "DESC");
        }


        if (sizeof($all_filters) != 0) {

            if (isset($all_filters['is_board_view']) && $all_filters['is_board_view']) {

                if (!isset($all_filters['is_admin_call']) || !$all_filters['is_admin_call'])
                    $product_brands = $product_brands->whereRaw('image_xbg_processed = 1');
            }

            // for /all API catgeory-wise filter
            if (
                isset($all_filters['category'])
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

            $product_brands = $product_brands
                ->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');

            $product_brands = DimensionsFilter::apply($product_brands, $all_filters);
            $product_brands = CollectionFilter::apply($product_brands, $all_filters);
            $product_brands = MaterialFilter::apply($product_brands, $all_filters);
            $product_brands = FabricFilter::apply($product_brands, $all_filters);
            $product_brands = DesignerFilter::apply($product_brands, $all_filters);
            $product_brands = MFDCountry::apply($product_brands, $all_filters);
            $product_brands = StyleFilter::apply($product_brands, $all_filters);

            if (
                isset($all_filters['seating'])
                && isset($all_filters['seating'][0])
            ) {
                $product_brands = $product_brands
                    ->whereRaw('seating REGEXP "' . implode("|", $all_filters['seating']) . '"');
            }

            if (
                isset($all_filters['shape'])
                && isset($all_filters['shape'][0])
            ) {
                $product_brands = $product_brands
                    ->whereRaw('shape REGEXP "' . implode("|", $all_filters['shape']) . '"');
            }

            if (isset($all_filters['color']) && strlen($all_filters['color'][0]) > 0) {

                $colors = implode("|", $all_filters['color']);
                $product_brands = $product_brands->whereRaw('color REGEXP "' . $colors . '"');
            }
            if(isset($all_filters['price_from']) && isset($all_filters['price_to'])){
                $product_brands = $product_brands
                        ->whereRaw('((min_price between '. $all_filters['price_from'][0] .' and '.$all_filters['price_to'][0].') or (max_price between '.$all_filters['price_from'][0].' and '.$all_filters['price_to'][0].'))');

            }
            else{
                    // 2. price_from
                    if (isset($all_filters['price_from'])) {
                        $product_brands = $product_brands
                            ->whereRaw('min_price >= ' . $all_filters['price_from'][0] . '');
                    }

                    // 3. price_to
                    if (isset($all_filters['price_to'])) {
                        $product_brands = $product_brands
                            ->whereRaw('max_price <= ' . $all_filters['price_to'][0] . '');
                    }
            }
        }

        $product_brands = $product_brands->groupBy('brand')->get();
        foreach ($product_brands as $b) {
            if (isset($all_brands[$b->brand])) {
                $all_brands[$b->brand]["enabled"] = true;
                if (isset($all_filters['brand'])) {
                    if (in_array($b->brand, $all_filters['brand'])) {
                        $all_brands[$b->brand]["checked"] = true;
                    }
                }
                $all_brands[$b->brand]["count"] = $b->products;
            }
        }

        $brands_holder = [];

        foreach ($all_brands as $name => $value) {
            array_push($brands_holder, $value);
        }

        return $brands_holder;
    }

    public static function get_price_filter($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only)
    {

        $p_to = $p_from = null;

        $price = DB::table('master_data');
        $price = $price->whereRaw("product_status='active'"); // Get filter only for active products
        $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);

        if (isset($all_filters['type']) && strlen($all_filters['type'][0]) > 0) {
            $LS_IDs = Product::get_sub_cat_LS_IDs($dept, $cat, $all_filters['type']);
        }
        // for /all API catgeory-wise filter
        if (
            isset($all_filters['category'])
            && strlen($all_filters['category'][0])
        ) {
            // we want to show all the products of this category
            // so we'll have to get the sub-categories included in this
            // catgeory
            $LS_IDs = SubCategory::get_sub_cat_LSIDs($all_filters['category']);
        }

        $price = $price->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');
        $price = DimensionsFilter::apply($price, $all_filters);
        $price = CollectionFilter::apply($price, $all_filters);
        $price = MaterialFilter::apply($price, $all_filters);
        $price = FabricFilter::apply($price, $all_filters);
        $price = DesignerFilter::apply($price, $all_filters);
        $price = MFDCountry::apply($price, $all_filters);
        $price = StyleFilter::apply($price, $all_filters);

        if (
            isset($all_filters['brand'])
            && strlen($all_filters['brand'][0]) > 0
        ) {
            $price = $price->whereIn('brand', $all_filters['brand']);
        }

        if (
            isset($all_filters['seating'])
            && isset($all_filters['seating'][0])
        ) {
            $price = $price
                ->whereRaw('seating REGEXP "' . implode("|", $all_filters['seating']) . '"');
        }

        if (isset($all_filters['is_board_view']) && $all_filters['is_board_view']) {

            if (!isset($all_filters['is_admin_call']) || !$all_filters['is_admin_call'])
                $price = $price->whereRaw('image_xbg_processed = 1');
        }

        if (
            isset($all_filters['shape'])
            && isset($all_filters['shape'][0])
        ) {
            $price = $price
                ->whereRaw('shape REGEXP "' . implode("|", $all_filters['shape']) . '"');
        }

        if (isset($all_filters['color']) && strlen($all_filters['color'][0]) > 0) {

            $colors = implode("|", $all_filters['color']);
            $price = $price->whereRaw('color REGEXP "' . $colors . '"');
        }

        // for getting new products
        if ($new_products_only == true) {
            $date_four_weeks_ago = date('Y-m-d', strtotime('-56 days'));
            $price = $price->whereRaw("created_date >= '" . $date_four_weeks_ago . "'");
            $price = $price->orderBy('new_group', 'asc');
            $price = $price->orderBy('created_date', 'desc');
        }
        // for getting products on sale
        else if ($sale_products_only == true) {

            $price = $price->whereRaw('min_price >  0')
                ->whereRaw('min_was_price > 0')
                ->whereRaw('(convert(min_was_price, unsigned) > convert(min_price, unsigned) OR convert(max_was_price, unsigned) > convert(max_price, unsigned))')
                ->orderBy('serial', 'asc'); 
        }
        
        // for getting products on is spacesaver
        if ($spacesaver_products_only == true) { 
            $price = $price->whereRaw('is_space_saver = "1"')
             ->orderBy('serial', 'asc'); 
        }
        else if (isset($trending)) {// Added for trending products
            $price = $price->join("master_trending", "master_data.product_sku", "=", "master_trending.product_sku");
            $price = $price->whereRaw("master_trending.trend_score>=20 and master_trending.is_active='1'");
            $price = $price->orderBy("master_trending.trend_score", "DESC");
        }
        /*else {
            $max = $price->max('max_price');
        }*/

        $min = $price->min('min_price');
        $max = $price->max('max_price');



       

        if (sizeof($all_filters) == 0) {
            // get min price and max price for all the products
            return [
                "min" => isset($min) ? $min : 0,
                "max" => isset($max) ? $max : 0
            ];
        } else {

            if (isset($all_filters['price_from'])) {
                $p_from = round($all_filters['price_from'][0]);
            }

            if (isset($all_filters['price_to'])) {
                $p_to = round($all_filters['price_to'][0]);
            }

            if ($p_from == 0) $p_from = floor($min);
            if ($p_to == 0) $p_to = ceil($max);

            return [
                "from" => $p_from,
                "to" => $p_to,
                "max" => isset($max) ? ceil($max) : 0,
                "min" => isset($min) ? floor($min) : 0,
            ];
        }
    }

    public static function get_color_filter($dept, $cat, $subCat, $all_filters, $request_colors, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only)
    {
        $colors = [
            "black" => "#000000",
            "blue" => "#0000ee",
            "brown" => "#a52a2a",
            "clear" => "#dcf0ef",
            "copper" => "#b87333",
            "gold" => "#FFD700",
            "green" => "#008000",
            "grey" => "#808080",
            "multicolor" => "#eeeeee",
            "pink" => "#FFC0CB",
            "purple" => "#800080",
            "red" => "#FF0000",
            "silver" => "#C0C0C0",
            "tan" => "#d2b48c",
            "white" => "#ffffff",
        ];

        $req_colors = $request_colors;

        $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);

        if (isset($all_filters['type']) && strlen($all_filters['type'][0]) > 0) {
            $LS_IDs = Product::get_sub_cat_LS_IDs($dept, $cat, $all_filters['type']);
        }
        // for /all API catgeory-wise filter
        if (
            isset($all_filters['category'])
            && strlen($all_filters['category'][0])
        ) {
            // we want to show all the products of this category
            // so we'll have to get the sub-categories included in this
            // catgeory
            $LS_IDs = SubCategory::get_sub_cat_LSIDs($all_filters['category']);
        }



        $products = DB::table("master_data")
            ->select(['LS_ID', 'color'])
            ->where('product_status','active') 
            ->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');

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

        $products = DimensionsFilter::apply($products, $all_filters);
        $products = CollectionFilter::apply($products, $all_filters);
        $products = MaterialFilter::apply($products, $all_filters);
        $products = FabricFilter::apply($products, $all_filters);
        $products = DesignerFilter::apply($products, $all_filters);
        $products = MFDCountry::apply($products, $all_filters);
        $products = StyleFilter::apply($products, $all_filters);


        if (sizeof($all_filters) > 0) {
            if (isset($all_filters['is_board_view']) && $all_filters['is_board_view']) {

                if (!isset($all_filters['is_admin_call']) || !$all_filters['is_admin_call'])
                    $products = $products->whereRaw('image_xbg_processed = 1');
            }

            if (
                isset($all_filters['brand'])
                && strlen($all_filters['brand'][0]) > 0
            ) {
                $products = $products->whereIn('brand', $all_filters['brand']);
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
        }
        /* if (isset($all_filters['color']) && strlen($all_filters['color'][0]) > 0) {
            $colors_from_request = implode("|", $all_filters['color']);
            $products = $products->whereRaw('color REGEXP "' . $colors_from_request . '"');
        }  */


        $products = $products->get();

        foreach ($colors as $key => $color_hex) {
            $colors[$key] = [
                'name' => ucfirst($key),
                'value' => strtolower($key),
                'hex' => $color_hex,
                'enabled' => false,
                'checked' => isset($req_colors) && in_array($key, $req_colors),
                'count' => 0
            ];
        }

        foreach ($products as $product) {
            $product_colors = explode(",", $product->color);
            foreach ($product_colors as $p_color) {

                if (strlen($p_color) > 0 && array_key_exists(strtolower($p_color), $colors)) {
                    $colors[strtolower($p_color)]['name'] = ucfirst($p_color);
                    $colors[strtolower($p_color)]['enabled'] = true;
                    $colors[strtolower($p_color)]['count'] += 1;
                }
            }
        }


        $colors_f = [];
        foreach ($colors as $key => $color) {
            array_push($colors_f, $color);
        }

        return $colors_f;
    }

    /*
     * category value can be present in $cat variable or in
     * $all_filter['category'] if the request is made from /all
     * products API.
    */
    public static function get_sub_cat_data($dept, $cat, $all_filters)
    {

        $sub_cat_LS_IDs = DB::table("mapping_core")
            ->select(["cat_name_short", "cat_name_url", "LS_ID", "cat_sub_url", "cat_sub_name"]);

        if ($dept != NULL && $dept != "all")
            $sub_cat_LS_IDs = $sub_cat_LS_IDs->where("dept_name_url", $dept);

        if ($cat != null)
            $sub_cat_LS_IDs = $sub_cat_LS_IDs->where("cat_name_url", $cat);

        if (isset($all_filters['category']) && sizeof($all_filters['category']) > 0) {
            // the request is comming from all products API and has a category LSID
            // attached with it
            $LS_ID = $all_filters['category'];
            // ADD LOGIC HERE FOR LIMITING THE SEARCH RESULTS
            // BASED ON THE SELECTED CATGEORY IN THE /ALL PRODUCTS API
            $LS_IDs = SubCategory::get_sub_cat_LSIDs($all_filters['category']);
            $sub_cat_LS_IDs = $sub_cat_LS_IDs->whereIn('LS_ID', $LS_IDs);
        }

        return $sub_cat_LS_IDs->whereRaw("LENGTH(cat_sub_name) != 0")->get();
    }

    // for product type filter ONLY!
    public static function get_filter_products_meta($dept, $cat, $subCat, $all_filters)
    {

        $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);

        // for /all API catgeory-wise filter
        if (
            isset($all_filters['category'])
            && strlen($all_filters['category'][0])
        ) {
            // we want to show all the products of this category
            // so we'll have to get the sub-categories included in this
            // catgeory
            $LS_IDs = SubCategory::get_sub_cat_LSIDs($all_filters['category']);
        }
        if (isset($all_filters['type']) && strlen($all_filters['type'][0]) > 0) {
            // comment this line if you want to show count for all those
            // sub_categories that are paased in the request.
            //$LS_IDs = Product::get_sub_cat_LS_IDs($dept, $cat, $all_filters['type']);

            // if uncommenting the above line, comment this one
            $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);
        }


        $products = DB::table("master_data")
            ->select(['LS_ID', 'color'])
            ->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');
        $products = DimensionsFilter::apply($products, $all_filters);
        $products = CollectionFilter::apply($products, $all_filters);
        $products = MaterialFilter::apply($products, $all_filters);
        $products = FabricFilter::apply($products, $all_filters);
        $products = DesignerFilter::apply($products, $all_filters);
        $products = MFDCountry::apply($products, $all_filters);
        $products = StyleFilter::apply($products, $all_filters);

        if (sizeof($all_filters) > 0) {

            if (
                isset($all_filters['brand'])
                && strlen($all_filters['brand'][0]) > 0
            ) {
                $products = $products->whereIn('brand', $all_filters['brand']);
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

            if (isset($all_filters['is_board_view']) && $all_filters['is_board_view']) {

                if (!isset($all_filters['is_admin_call']) || !$all_filters['is_admin_call'])
                    $products = $products->whereRaw('image_xbg_processed = 1');
            }
        }

        return $products->get();
    }

    public static function get_product_type_filter($dept, $category, $subCat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only)
    {

        // for all products API
        // $dept will be 'all' and the catgeories will come from
        // $all_filters data, we want to show the type filter only when some
        // catgeory is selected, so return an empty array for types if
        // no categories is selected
        $do_process = true;

        if ($dept == 'all') {
            if (!isset($all_filters['category']))
                $do_process = false;
            else if (sizeof($all_filters['category']) == 0)
                $do_process = false;
        }

        if ($do_process == true) {
            $products = Product::get_filter_products_meta($dept, $category, $subCat, $all_filters);

            $sub_cat_arr = [];

            $sub_cat_LS_IDs = Product::get_sub_cat_data($dept, $category, $all_filters);

            foreach ($sub_cat_LS_IDs as $cat) {
                $selected = false;
                if (strtolower($cat->cat_sub_url) == strtolower($subCat)) $selected = true;
                $sub_cat_arr[$cat->cat_sub_url] = [
                    "name" => $cat->cat_sub_name,
                    "value" => strtolower($cat->cat_sub_url),
                    "enabled" => false,
                    "checked" => $selected,
                    "count" => 0
                ];
            }

            //echo "<pre>" . print_r($all_filters, true);
            foreach ($sub_cat_LS_IDs as $cat) {
                foreach ($products as $p) {
                    if (strpos($p->LS_ID, (string) $cat->LS_ID) !== false) {
                        if (isset($sub_cat_arr[$cat->cat_sub_url])) {
                            $sub_cat_arr[$cat->cat_sub_url]["enabled"] = true;
                            $sub_cat_arr[$cat->cat_sub_url]["count"]++;

                            if (isset($all_filters['type'])) {
                                $sub_category = strtolower($cat->cat_sub_url);

                                if (in_array($sub_category, $all_filters['type'])) {
                                    $sub_cat_arr[$cat->cat_sub_url]["checked"] = true;
                                }
                            }
                        }
                    }
                }
            }

            $arr = [];
            foreach ($sub_cat_arr as $key => $value) {
                array_push($arr, $value);
            }
        } else {
            $arr = [];
        }

        $color_filter = isset($all_filters['color']) && strlen($all_filters['color'][0]) > 0 ? $all_filters['color'] : null;
        return [
            'colorFilter' => Product::get_color_filter($dept, $category, $subCat, $all_filters, $color_filter, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only),
            'productTypeFilter' => $arr
        ];
    }

    public static function get_product_obj($products, $all_filters, $dept, $cat, $subCat, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only, $is_listing_API_call = null, $is_details_minimal = false)
    {

        $p_send              = [];
        $filter_data         = [];
        $brand_holder        = [];
        $price_holder        = [];
        $product_type_holder = [];

        $westelm_cache_data  = DB::table("westelm_products_skus")
            ->selectRaw("COUNT(product_id) AS product_count, product_id")
            ->groupBy("product_id")
            ->get();
        $products_to_ignore = DB::table("products_ignore")->select("sku")->get()->toArray();
        $products_to_ignore = array_column($products_to_ignore, "sku");

        // get inventory list
        $inventory_products_db = DB::table('lz_inventory')
            ->select(["product_sku", "quantity", "message", "price", "ship_code"])
            ->where('is_active', 1)
            ->get();

        // get user cart
        $cart = Cart::cart();
        $user_cart = $cart['products'];

        /* ============== PREPROCESS FOR INVENTORY DETAILS FOR LISTING API ================*/
        $user_skus = [];
        foreach ($user_cart as  $prod) {
            $prod = (object) $prod;
            $user_skus[$prod->product_sku] = $prod;
        }

        $inventory_prod = [];
        foreach ($inventory_products_db as $prod) {
            $inventory_prod[$prod->product_sku] = $prod;
            $inventory_prod[$prod->product_sku]->is_low = $prod->quantity <= 5;
            $inventory_prod[$prod->product_sku]->is_shipping_free = ($prod->ship_code == Config::get('shipping.free_shipping'));

            // remove extra property from product object to save data transfer.
            unset($prod->ship_code);
        }

        /* ===============================================================================*/

        $westelm_variations_data = [];

        if (sizeof($westelm_cache_data) > 0) {
            foreach ($westelm_cache_data as $row) {
                $westelm_variations_data[$row->product_id] = $row->product_count;
            }
        }

        // removing waste data
        $westelm_cache_data = [];

        // check if the prodcuts is in a wishlist
        $wishlist_products = [];
        $is_authenticated = Auth::check();
        if ($is_authenticated) {
            $user = Auth::user();
            $w_products = DB::table("user_wishlists")
                ->select("product_id")
                ->where("user_id", $user->id)
                ->where("is_active", 1)
                ->get();

            // cleaning the array
            foreach ($w_products as $p)
                array_push($wishlist_products, $p->product_id);
        }



        foreach ($products as $product) {

            $product_LS_IDs = explode(",", $product->LS_ID);
            $skip_product = false;
            foreach ($product_LS_IDs as $LS_ID) {
                if (intval($LS_ID) >= 828 && intval($LS_ID) <= 832) {
                    $skip_product = true;
                    break;
                }
            }

            if ($skip_product)
                continue;

            if (!in_array($product->product_sku, $products_to_ignore)) {
                $isMarked = false;
                if ($is_authenticated) {
                    if (in_array($product->product_sku, $wishlist_products)) {
                        $isMarked = true;
                    }
                }

                /*=========================== INVENTORY DETAILS IN LISTING API ================================*/

                $product->in_inventory = false;
                $product->inventory_product_details = null;

                $product_sku = $product->product_sku;
                if (isset($inventory_prod[$product_sku])) {
                    $product->in_inventory = true;

                    $product_count_remaining = $inventory_prod[$product_sku]->quantity;
                    // if sku present in the cart then minus the count from inventory
                    if (isset($user_skus[$product_sku]))
                        $product_count_remaining -= $user_skus[$product_sku]->count;

                    $inventory_prod[$product_sku]->quantity = $product_count_remaining;
                    $product->inventory_product_details = (array) $inventory_prod[$product_sku];
                }

                /*==============================================================================================*/


                $variations = Product::get_variations($product, $westelm_variations_data, $is_listing_API_call);
                array_push($p_send, Product::get_details($product, $variations, $is_listing_API_call, $isMarked, false, $is_details_minimal));
            }
        }

        $brand_holder = Product::get_brands_filter($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only);
        $price_holder = Product::get_price_filter($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only);
        $product_type_holder = Product::get_product_type_filter($dept, $cat, $subCat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only)['productTypeFilter'];
        $color_filter = Product::get_product_type_filter($dept, $cat, $subCat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only)['colorFilter'];

        $seating_filter = Product::get_seating_filter($dept, $cat, $all_filters,  $sale_products_only,$new_products_only,$trending,$spacesaver_products_only);  //return $seating_filter;
        $shape_filter = Product::get_shape_filter($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only);

        if ($dept == "all") {
            if (!isset($all_filters['category']))
                $all_filters['category'] = [];

            $brand_filter = isset($all_filters['brand'][0]) ? $all_filters['brand'][0] : null;
            $category_holder =  Product::get_all_dept_category_filter($brand_filter, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only);
        }

        $dimension_filter = DimensionsFilter::get_filter($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only);//return $dimension_filter;
        
        //  return $dimension_filter;
        $filter_data = [
            "brand"  => $brand_holder,
            "price"  => $price_holder,
            "category" => $dept == "all" ? $category_holder : null,
            "type" => $product_type_holder,
            "color" => $color_filter,
            "width" => [$dimension_filter['dim_width']],
            "length" => [$dimension_filter['dim_length']],
            "depth" => [$dimension_filter['dim_depth']],
            "height" => [$dimension_filter['dim_height']],
            "diameter" => [$dimension_filter['dim_diameter']],
            "square" => [$dimension_filter['dim_square']],
            "material" => MaterialFilter::get_filter_data($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending,$spacesaver_products_only),
            "style" => StyleFilter::get_filter_data($dept, $cat, $all_filters,$sale_products_only,$new_products_only,$trending,$spacesaver_products_only),
            "shape" => $shape_filter, 
            "seating" => $seating_filter,
        // "firmness" => $firmness_filter,
            "designer" => DesignerFilter::get_filter_data($dept, $cat, $all_filters,$sale_products_only,$new_products_only,$trending,$spacesaver_products_only),
            "collection" => isset($all_filters['collection']) ? $all_filters['collection'] : null,
            //"fabric" => FabricFilter::get_filter_data($dept, $cat, $all_filters, $sale_products_only,$new_products_only,$trending),
            "country" => MFDCountry::get_filter_data($dept, $cat, $all_filters,$sale_products_only,$new_products_only,$trending,$spacesaver_products_only),
        ];

        //$dept, $cat, $subCat
        $dept_info = DB::table("mapping_core");

        if ($dept != null)
            $dept_info = $dept_info->where("dept_name_url", $dept);

        if ($cat != null)
            $dept_info = $dept_info->where("cat_name_url", $cat);
        else
            $dept_info = $dept_info->whereRaw("LENGTH(cat_name_url) = 0");

        $dept_info = $dept_info->whereRaw("LENGTH(dept_name_long) > 0")
            ->whereRaw("LENGTH(cat_image) > 0")
            ->select(['dept_name_long', 'cat_name_long', 'cat_name_short', 'cat_image', 'filter_label'])
            ->limit(1)
            ->get();

        if (isset($dept_info[0])) {
            $d = $dept_info[0];
            $seo_data = [
                "page_title" => $d->cat_name_long,
                "full_title" => $d->dept_name_long . " "  . $d->cat_name_short,
                "email_title" => $d->filter_label,
                "email_title" => $d->filter_label,
                "dept_name_long" => $d->dept_name_long,
                "cat_name_long" => $d->cat_name_long,
                "cat_name_short" => $d->cat_name_short,
                "cat_image" => Product::$base_siteurl . $d->cat_image,
                "description" => "Search hundreds of " . $d->cat_name_long  . " from top brands at once. Add to your room designs with your own design boards.",
                "image_url" => Product::$base_siteurl . $d->cat_image

            ];
        } else {
            $seo_data = null;
        }

        return [
            "seo_data" => $seo_data,
            "total"      => $all_filters['count_all'],
            "constructor_count" => Product::$count,
            "sortType"  => isset($all_filters['sort_type']) ? $all_filters['sort_type'] : null,
            "limit"      => isset($all_filters['limit']) ? $all_filters['limit'] : null,
            "filterData" => $filter_data,
            "products"   => $p_send,
        ];
    }

    public static function get_details(
        $product,
        $variations,
        $is_listing_API_call = null,
        $isMarked = false,
        $isTrending = false,
        $is_details_minimal = false
    ) {

        // NOTE: $isListingCall and $isMarked will also be true for wishlish API call

        // $is_details_minimal => send xbg image instead of main_image. Used in the Design Board section of the site.
        // checking if the variations data has variations buttons (extras) data as well

        $extras = null;
        $hashmap = null;
        $user = Auth::user();
        if (isset($variations['hashmap'])) {
            $hashmap = $variations['hashmap'];
        }

        if (isset($variations['variations'])) {
            $extras = $variations['extras'];
            $variations = $variations['variations'];
        }

        $p_val = $wp_val = $discount = null;

        $is_price = Utility::rm_comma($product->min_price);
        $was_price = Utility::rm_comma($product->max_price);
        $min_was_price = Utility::rm_comma($product->min_was_price);
        $max_was_price = Utility::rm_comma($product->max_was_price);

        if ($is_price != $was_price) {
            // price is ranged 
            $is_price = $is_price . "-" . $was_price;
        }

        if ($min_was_price != $max_was_price)
        //if(($min_was_price != $is_price) && ($max_was_price != $was_price))	
        {
            // sale product 
            $was_price = $min_was_price . "-" . $max_was_price;
        } else {
            $was_price = $min_was_price;
        }

        $p_price = str_replace("$", "", $is_price);
        $wp_price = str_replace("$", "", $was_price);

        $price_bits = explode("-", $p_price);
        $was_price_bits = explode("-", $wp_price);

        if (isset($price_bits[1]) && isset($was_price_bits[1])) {
            $p_val = $price_bits[0];
            $wp_val = $was_price_bits[0];
        } else {
            $p_val = $p_price;
            $wp_val =  $wp_price;
        }

        // if product in inventory then calculate the discount
        // based on inventory price value
        if (isset($product->in_inventory) && $product->in_inventory) {
            $p_val = $product->inventory_product_details['price'];
            $product->price = (string) $p_val;
        }

        if (is_numeric($p_val) && is_numeric($wp_val) && $wp_val > 0) {
            $discount = (1 - ($p_val / $wp_val)) * 100;
            $discount = number_format((float) $discount, 2, '.', '');
        }

        $is_new = false;
        if (strlen($product->created_date) > 0) {
            $diff = strtotime(date("Y-m-d H:i:s")) - strtotime($product->created_date);
            $days = $diff / 60 / 60 / 24;
            if ($days < 2 * 4 * 7) $is_new = true;


            // making product added date to fixed
            /* $jan192020 = strtotime('2020/01/19'); // 4
            $product_date = strtotime($product->created_date); // 5
            if ($jan192020 > $product_date) $is_new = false;
            else $is_new = true; */
        }

        $main_image = ($is_details_minimal) ?  $product->image_xbg : $product->main_product_images;
        $product_sub_details_arr = [];

        if (isset($product->product_sub_details) && $product->product_sub_details != '') {
            $sub_details_arr = json_decode($product->product_sub_details);
            if (is_array($sub_details_arr)) {
                foreach ($sub_details_arr as $ps_details) {
                    if (isset($ps_details->image)) {
                        $ps_details->image = Product::$base_siteurl . $ps_details->image;
                    }
                    array_push($product_sub_details_arr, $ps_details);
                }
            }
        }


        // for wishlist
        $data =  [
            //'id'               => isset($product->id) ? $product->id : rand(1, 10000) * rand(1, 10000),
            'brand'            => $product->brand,
            'sku'              => $product->product_sku,
            'is_new'           => $is_new,
            'redirect'         => isset($product->redirect) ? $product->redirect : false,
            'in_inventory'     => isset($product->in_inventory) ? $product->in_inventory : false,
            'inventory_product_details' => isset($product->inventory_product_details) ? $product->inventory_product_details : null,
            //    'sku_hash'         => $product->sku_hash,
            'site'             => $product->name,
            'return_policy'    => $product->return_policy,
            'name'             => $product->product_name,
            'product_url'      => urldecode($product->product_url),
            'product_detail_url' => Product::$base_siteurl . "/product/" . $product->product_sku,
            'is_price'         => $is_price,
            'was_price'        => $was_price,
            'percent_discount' => $discount,
            //'model_code'       => $product->model_code,
            'seating'          => isset($product->seating) ? $product->seating : null,
            //    'description'      => preg_split("/\\[US\\]|<br>|\\n/", $product->product_description),
            //    'dimension'        => $product->brand == "cb2" ? Product::cb2_dimensions($product->product_dimension) : $product->product_dimension,
            //    'thumb'            => preg_split("/,|\\[US\\]/", $product->thumb),
            'color'            => $product->color,
            //    'images'           => array_map([__CLASS__, "baseUrl"], preg_split("/,|\\[US\\]/", $product->images)),
            //    'features'         => preg_split("/\\[US\\]|<br>|\\n/", $product->product_feature),
            'collection'       => $product->collection,
            //    'set'              => $product->product_set,
            'condition'        => (isset($product->product_condition) || strlen($product->product_condition) > 0) ? $product->product_condition : null,
            //    'created_date'     => $product->created_date,
            //    'updated_date'     => $product->updated_date,
            //    'on_server_images' => array_map([__CLASS__, "baseUrl"], preg_split("/,|\\[US\\]/", $product->product_images)),
            'main_image'       => Product::$base_siteurl . $main_image,
            'reviews'          => $product->reviews,
            'rating'           => (float) $product->rating,
            'wishlisted'       => $isMarked,

            // add availablilty data for all products
            'is_back_order'    => isset($product->is_back_order) ? $product->is_back_order : null,
            'back_order_msg'   => isset($product->back_order_msg) ? $product->back_order_msg : null,
            'back_order_msg_date' => isset($product->back_order_msg_date) ? $product->back_order_msg_date : null,
            'online_msg'       => isset($product->online_msg) ? $product->online_msg : "",

            'product_assembly'       => isset($product->product_assembly) ? $product->product_assembly : null,
            'product_care'       => isset($product->product_care) ? $product->product_care : null,
            'product_sub_details'   => isset($product->product_sub_details) ? $product_sub_details_arr : null
            //    'LS_ID'            => $product->LS_ID,
        ];

        /* if ($product->brand == "westelm" && !$is_listing_API_call ) {
            $data['filters'] = end($variations)['filters'];
            array_pop($variations);
        } */

        // call coming from the board
        if ($is_details_minimal || $isMarked) {
            $data['board_thumb'] = (isset($product->image_xbg_thumb) && strlen($product->image_xbg_thumb)) > 0 ? env('APP_URL') . $product->image_xbg_thumb : null;
            $data['board_cropped'] = (isset($product->image_xbg_cropped) && strlen($product->image_xbg_cropped)) > 0 ? env('APP_URL') . $product->image_xbg_cropped : null;
        }


	   if (isset($variations) && !$is_details_minimal) {
            if (is_array($variations)) {
                for ($i = 0; $i < sizeof($variations); $i++) {
                    if (isset($variations[$i]['image']) && gettype($variations[$i]['image'] == gettype([]))) {
                        if (
                            $variations[$i]['image'] === Product::$base_siteurl
                            || strlen($variations[$i]['image']) == 0
                        ) {

                            $variations[$i]['image'] = $data['main_image'];

                            if (isset($variations[$i]['variation_sku'])) {
                                $main_prod_image = Utility::get_main_image($variations[$i]['variation_sku']);
                                if (isset($main_prod_image)) {
                                    $variations[$i]['image'] = $main_prod_image;
                                }
                            }
                        }
                    }
                }
            }

            $data['variations'] = $variations;
        }


        $desc_BRANDS = Config::get('meta.to_format_brands');
        $dims_from_features = Config::get('meta.dims_form_feature_brands'); // these extract dimensions data from features data.
        $sets_enabled_brands = Config::get('meta.sets_enabled_brands');
        $dims_text = in_array($product->name, $dims_from_features) ? $product->product_feature : $product->product_dimension;
        $children = null;
        if (!$is_listing_API_call) {

            // All alpha-numeric SKUs of `sets enabled brands` products have child products
            if (in_array($product->brand, $sets_enabled_brands)) {

                if (!is_numeric($product->product_sku)) {
                    // it is alpha-numeric
                    $product_table = Utility::get_sets_enabled_brand_table($product->brand);
                    $child_rows = DB::table($product_table)
                        ->whereRaw("product_set LIKE '%" . $product->product_sku . "%'")
                        ->get();

                    $children = [];
                    foreach ($child_rows as $row) {
                        $product_set_inventory_details = Inventory::get_product_from_inventory($user, $row->product_sku);
                        $price = $was_price = null;
                        if ($product_set_inventory_details['in_inventory']) {
                            $price = $product_set_inventory_details['inventory_product_details']['price'];
                            $was_price = $product_set_inventory_details['inventory_product_details']['was_price'];
                        }
                        $set = [
                            'parent_sku' => $product->product_sku,
                            'sku' => $row->product_sku,
                            'name' => $row->product_name,
                            'image' => env('APP_URL') . $row->main_product_images,
                            'link' => $row->product_url,
                            'price' => isset($price) ? $price : $row->price,
                            'was_price' => isset($was_price) ? $was_price : $row->was_price
                        ];

                        $set = array_merge($product_set_inventory_details, $set);

                        array_push($children, $set);
                    }
                }
            }

            $data['collections'] = Collections::get_collections($product->product_sku, $product->collection, $product->brand);
            $data['set'] = $children;
            $data['description'] = in_array($product->name, $desc_BRANDS)  ? Utility::format_desc_new($product->product_description) : preg_split("/\\[US\\]|<br>|\\n/", $product->product_description);

            $dimensions_data = Dimension::normalize_dimension($dims_text, $product->brand);
            $data['dimension'] = isset($dimensions_data) ? $dimensions_data : [];

            //$data['thumb'] = preg_split("/,|\\[US\\]/", $product->thumb);
            $data['features'] = in_array($product->name, $desc_BRANDS) ? Utility::format_desc_new($product->product_feature) : preg_split("/\\[US\\]|<br>|\\n|\|/", $product->product_feature);
            $data['on_server_images'] = array_map([__CLASS__, "baseUrl"], preg_split("/,|\\[US\\]/", $product->product_images));
            $data['department_info'] = Department::get_department_info($product->LS_ID);
            $data['selections'] = $extras;
            //$data['hashmap'] = $hashmap;


            // these values are used in backend admin APIs
            $data['xbg_primary'] = isset($product->image_xbg_select_primary) ? $product->image_xbg_select_primary : null;
            $data['xbg_secondary'] = isset($product->image_xbg_select_secondary) ? $product->image_xbg_select_secondary : null;
            return $data;
        } else {

            if ($isTrending) {
                $data['description'] = in_array($product->name, $desc_BRANDS)  ? Utility::format_desc_new($product->product_description) : preg_split("/\\[US\\]|<br>|\\n/", $product->product_description);
            }

            $product_inventory_details = Inventory::get_product_from_inventory($user, $product->product_sku);
            $data = array_merge($product_inventory_details, $data);
            return $data;
        }
    }



    public static function cb2_dimensions($json_string)
    {

        if ($json_string === "null") return [];

        $dim = json_decode($json_string);

        if (json_last_error()) return [];

        $d_arr = [];
        $dd_arr = [];

        foreach ($dim as $d) {
            if ($d->hasDimensions) {
                array_push($d_arr, $d);
            }
        }

        //return $json_string;
        return $d_arr;
    }

    public static function baseUrl($link)
    {
        return Product::$base_siteurl . $link;
    }

    public static function get_c_variations($sku, $variation_table)
    {
        $cols = [
            "product_sku",
            "variation_sku",
            "swatch_image",
            "variation_image",
            //"variation_name",
            "has_parent_sku",
            "price",
            "was_price"
        ];

        $product_variations = [];
        $variation_choices = [];
        $variations = DB::table($variation_table)
            ->select($cols)
            ->distinct('variation_sku')
            ->where('product_sku', $sku)
            ->where('is_active', 'active')
            ->get();

        // only check for product_sku and variation_sku if size of variations is > 1 (ref issue #160 -> API -> last point)

        foreach ($variations as $variation) {
            $link =  "/product/";

            if ($variation->has_parent_sku) {
                $link .= $variation->variation_sku;
            } else {
                $link .= $variation->product_sku;
            }

            if ($variation->has_parent_sku == 1 && !isset($variation->variation_image)) {

                // cb2_products
                $v_image = DB::table("master_data")
                    ->where("product_sku", $variation->variation_sku)
                    ->select(['main_product_images'])->get();

                if (isset($v_image[0])) {
                    $v_image = $v_image[0]->main_product_images;
                } else {
                    $v_image = $variation->variation_image;
                }
            } else {
                $v_image = $variation->variation_image;
            }

            // if last char of swatch image in DB is '/' then there is no
            // valid image for the SKU. It is the prefix of image path.
            // pass NULL ot API in such cases.
            $swatch_image = null;
            $swatch_length = strlen($variation->swatch_image);
            if (isset($variation->swatch_image)) {
                if ($variation->swatch_image[$swatch_length - 1] == "/") {
                    $swatch_image = null;
                } else {
                    $swatch_image = Product::$base_siteurl . $variation->swatch_image;
                }
            }
            $v = [
                "product_sku" => $variation->product_sku,
                "variation_sku" => $variation->variation_sku,
                //"name" => $variation->variation_name,
                "has_parent_sku" => $variation->has_parent_sku == 1 ? true : false,
                "swatch_image" => $swatch_image,
                "image" => isset($v_image) ? Product::$base_siteurl . $v_image : null,
                "link" => $link,
                "is_button" => !isset($swatch_image),
                "label" => !isset($swatch_image) ? null : null,
                "price" => isset($variation->price) ? $variation->price : null,
                "was_price" => isset($variation->was_price) ? $variation->was_price : null,
            ];

            if (sizeof($variations) == 1) {
                if ($variation->product_sku != $variation->variation_sku) {
                    array_push($product_variations, $v);
                }
            } else {
                array_push($product_variations, $v);
            }

            if ($swatch_image == null) {
                array_push($variation_choices, [
                    'label' => isset($variation->variation_name) ? $variation->variation_name : "",
                    'link' => $link,
                    'var_sku' => $variation->variation_sku
                ]);
            }
        }

        $variation_extras = [
            'type' => 'redirect',
            'options' => $variation_choices
        ];


        return [
            'variations' => $product_variations,
            'extras' => $variation_extras
        ];
    }

    public static function get_pier1_variations($product)
    {
        $product_variations = [];

        if ($product->master_id == null) return $product_variations;

        $executionStartTime = microtime(true);
        $variations = DB::table("pier1_products")
            ->where("product_status", "active")
            ->where("master_id", $product->master_id)
            ->get();

        $executionEndTime =  microtime(true) - $executionStartTime;

        foreach ($variations as $variation) {

            if ($product->product_sku != $variation->product_sku) {
                array_push($product_variations, [
                    "time_taken" => $executionEndTime,
                    "product_sku" => $product->product_sku,
                    "variation_sku" => $variation->product_sku,
                    "name" => $variation->color,
                    "has_parent_sku" => true,
                    "image" => Product::$base_siteurl . $variation->main_product_images,
                    "link" =>  "/product/" . $variation->product_sku,
                    "swatch_image" => Product::$base_siteurl . $variation->main_product_images
                ]);
            }
        }

        return $product_variations;
    }

    public static function get_filter_key($key)
    {
        $key = preg_replace('/please|Please|select|Select/', '', $key);
        return (strtolower(preg_replace("/[\s]+/", "_", trim($key))));
    }

    public static function get_filter_label($key)
    {
        $key = preg_replace('/please|Please|select|Select/', '', $key);
        return $key;
    }

    public static function get_product_variations($product, $wl_v, $is_listing_API_call = null, $brand = 'westelm')
    {
        $cols = Config::get('meta.variations_cols');
        $variation_table = Config::get('tables.variations.' . $brand . '.table');
        if (!isset($variation_table) || strlen($variation_table) == 0) {
            $variation_table = "seller_products_variations";
        }

        $attr_count = $brand == 'westelm' ? 6 : 3;
        Log::info("VARIATIONS | brand: " . $brand);
        $variations_extra = [];
        $swatch_map = [];
        $color_map = Product::$color_map;

        if (isset($product->product_sku)) {
            if (isset($wl_v[$product->product_sku]) || $brand != 'westelm') {
                $var = DB::table($variation_table)
                    ->select($cols)
                    ->where('status', 'active');
                //$var = $var->groupBy("swatch_image_path");

                $var = $var->where("product_id", (string)$product->product_sku)
                    ->whereRaw("LENGTH(swatch_image_path) != 0");

                if ($is_listing_API_call) $var = $var->limit(7);
                //->limit(20)
                $var = $var->get();

                // handle for - if any product has empty swatch image, then include all the entries.
                $var_add = DB::table($variation_table)
                    ->select($cols)
                    ->where('status', 'active')
                    ->where('product_id', (string)$product->product_sku)
                    ->whereRaw('LENGTH(swatch_image_path) = 0')
                    ->get();

                Log::info("VARIATIONS | var with swatch_image_path: " . sizeof($var) . " var with no swatch image path: " . sizeof($var_add));
                $var = $var->merge($var_add);
                $var = $var->all();

                // removing the check for variations = 1
                // now FE is responsible for handling this condition
                //if (sizeof($var) == 1) return [];

                //return $var;
                $variations = [];
                $variation_filters = [];
                $swatches = [];
                $extras_key = [];

                $variation_extras = [];



                foreach ($var as $prod) {
                    $features = [];
                    for ($i = 1; $i <= $attr_count; $i++) {
                        $col = "attribute_" . $i;
                        $str = $prod->$col;
                        $str_exp = explode(":", $str);
                        if (isset($str_exp[0]) && isset($str_exp[1])) {
                            //echo $filter_key . "<br>";

                            $filter_key = Product::get_filter_key($str_exp[0]);
                            $features[$filter_key] = $str_exp[1];

                            // set keys for variations data
                            if (isset($extras_key[$filter_key])) {
                                if (is_array($extras_key[$filter_key])) {
                                    if (!in_array($str_exp[1], $extras_key[$filter_key])) {
                                        array_push($extras_key[$filter_key], $str_exp[1]);
                                    }
                                }
                            } else {
                                $extras_key[$filter_key] = [];
                                array_push($extras_key[$filter_key], $str_exp[1]);
                            }

                            // setting array indexes for each filter category
                            if (!isset($variation_filters[$filter_key]))
                                $variation_filters[$filter_key] = [];

                            // saving unique data values for the filter value display
                            /*if (!in_array($str_exp[1], $variation_filters[$str_exp[0]], true)) {
                                array_push($variation_filters[$str_exp[0]], [
                                    "name" => $str_exp[1],
                                    "value" => strtolower(preg_replace("' '", "-", $str_exp[1])),
                                    "enabled" => true
                                ]);
                            }*/
                            $found = false;
                            //echo sizeof($variation_filters[$filter_key]);
                            foreach ($variation_filters[$filter_key] as $filter) {
                                //echo "comparing " . $filter["value"] . " %% " . $str_exp[1] . "<br>";
                                if (isset($filter["name"])) {
                                    if ($filter["name"] == $str_exp[1]) {
                                        $found = true;
                                        break;
                                    }
                                }
                            }

                            if (!$found) {
                                array_push($variation_filters[$filter_key], [
                                    "name" => $str_exp[1],
                                    "value" => strtolower(preg_replace("' '", "-", $str_exp[1])),
                                    "enabled" => true
                                ]);
                            }
                        }
                    }

                    $name = "";
                    $features['color_group'] = null;
                    if (isset($features['color'])) {
                        $name = $features['color'];
                        $name_arr = explode(" ", $name);

                        // find the hex code for color;
                        foreach ($name_arr as $name_str) {
                            if (isset($color_map[strtolower($name_str)])) {
                                $features['color_group'] = $color_map[strtolower($name_str)]['name'];
                                break;
                            }
                        }
                        if (isset($features['fabric'])) {
                            $name .= ", " . $features['fabric'];

                            if (!isset($swatch_map[$name])) {
                                $swatch_map[$name] = $prod->swatch_image_path;
                            } else {
                                $prod->swatch_image_path = $swatch_map[$name];
                            }
                        }
                    }

                    $is_dropdown = false;
                    $extras = [];
                    $multi_select_filters = ['color_group', 'fabric'];
                    $excluded_options = ['fabric'];
                    foreach ($extras_key as $key => $arr) {
                        if (sizeof($arr) > 4) {
                            $is_dropdown = true;
                        }

                        $select_type = in_array($key, $multi_select_filters) ? "multi_select" : "single_select";
                        $select_type = in_array($key, $excluded_options) ? "excluded" : $select_type;

                        $select_type = $select_type;// ($key == 'color' && Utility::match_exclude_LDIS($product->LS_ID)) ? "excluded" : $select_type;

                        if ($key == "color") {
                            $extras["color_group"] = [
                                'select_type' => 'excluded',
                                'options' => [],
                                'hexcodes' => []
                            ];
                        }
                        $extras[$key] = [
                            'select_type' => $select_type,
                            'options' => []
                        ];
                    }

                    foreach ($extras_key as $key => $arr) {
                        if ($key == "color") {
                            foreach ($arr as $k) {
                                $color_names = explode(" ", $k);
                                foreach ($color_names as $color_name) {
                                    $color_name = strtolower($color_name);
                                    if (isset($color_map[$color_name])) {

                                        if (!in_array($color_map[$color_name]['name'], $extras['color_group']['options']))
                                            $extras['color_group']['options'][] = $color_map[$color_name]['name'];

                                        if (!in_array($color_map[$color_name]['hexcode'], $extras['color_group']['hexcodes']))
                                            $extras['color_group']['hexcodes'][] = $color_map[$color_name]['hexcode'];
                                    }
                                }
                            }
                        }


                        foreach ($arr as $k) {
                            array_push($extras[$key]['options'], $k);
                            sort($extras[$key]['options']);
                        }
                    }

                    $imgarr = [];
                    $imgstr = '';
                    if (isset($prod->image_path) && $prod->image_path != '') {
                        $arr = explode(",", $prod->image_path);
                        for ($i = 0; $i < sizeof($arr); $i++) {
                            $imgarr[$i] = Product::$base_siteurl . $arr[$i];
                        }
                        $imgstr = implode(',', $imgarr);
                    }


                    $variation_extras = $extras;

                    array_push($variations, [
                        "product_sku" => $product->product_sku,
                        "variation_sku" => $prod->sku,
                        "name" => $name,
                        "features" => $features,
                        "has_parent_sku" => isset($prod->has_parent_sku) ? (bool) $prod->has_parent_sku : false,
                        //"image" => Product::$base_siteurl . $prod->image_path,
                        "image" => sizeof($imgarr) > 0 ? $imgstr : Product::$base_siteurl,
                        "link" =>  "/product/" . $product->product_sku,
                        "swatch_image" => strlen($prod->swatch_image_path) != 0 ? Product::$base_siteurl . $prod->swatch_image_path : null,
                        "price" => $prod->price,
                        "was_price" => $prod->was_price
                    ]);
                }

                // override color filter select type here.
                Utility::update_color_filter_type($variations, $variation_extras);


                /* if (!$is_listing_API_call) {
                    array_push($variations, [
                        "filters" => Product::get_all_variation_filters($product->product_sku)
                    ]);
                } */

                $hashmap = [];
                foreach ($variations as $variation) {
                    $features = $variation['features'];
                    ksort($features);

                    $str = '';
                    foreach ($features as $f => $val) {
                        $str .= $val;
                    }

                    $hashmap[md5($str)] = [
                        'price' => $variation['price'],
                        'was_price' => $variation['was_price'],
                        'image' => $variation['image'],
                        'var_sku' => $variation['variation_sku']

                    ];
                }

                return [
                    'variations' => $variations,
                    'extras' => $variation_extras,
                    'hashmap' => $hashmap
                ];
            }
        } else {
            // product SKU not found in the incomming params 
            return [
                'error' => 'incorrect params passed in method > get_westelm_variations',
                'file' => __FILE__
            ];
        }

        return [];
    }

    public static function get_variations($product, $wl_v = null, $is_listing_API_call = null)
    {


        // Listing API update. Just send count of variations in listing API
        if ($is_listing_API_call)
            return $product->variations_count;

        $variations = [];

        if (isset($product->site_name)) {
            $variations =  Product::get_product_variations($product, $wl_v, $is_listing_API_call, $product->site_name);
        }

        if (isset($variations['variations']) && is_array($variations['variations'])) {
            foreach ($variations['variations'] as &$var) {
                $inv_product = Inventory::get_product_from_inventory(Auth::user(), $var['variation_sku']);

                // override variations price data with inventory data
                if ($inv_product['in_inventory']) {
                    $var['price'] = $inv_product['inventory_product_details']['price'];
                    $var['was_price'] = $inv_product['inventory_product_details']['was_price'];
                }

                $var = array_merge($var, $inv_product);
            }
        }


        return $variations;
    }

    // LS_ID can be comma separated.
    public static function get_product_LS_ID($sku)
    {

        $prod = Product::where("product_sku", $sku)
            ->get();
        if (sizeof($prod) != 0) {
            return $prod[0]->LS_ID;
        }

        return null;
    }

    public static function is_redirect($sku = null)
    {
        $row = DB::table("product_redirects")
            ->select(["redirect_sku", "brand"])
            ->where("sku", $sku)
            ->where("is_active", 1)
            ->get();

        return isset($row[0]) ? $row[0] : null;
    }


    public static function product_seo($sku, $ls_id)
    {

        $rows = DB::table(Config::get('tables.LS_ID_mapping'))
            ->whereIn('LS_ID', explode(",", $ls_id))
            ->get();

        $product_details = DB::table(Config::get('tables.master_table'))
            ->select([
                Config::get('tables.master_table') . ".product_name",
                Config::get('tables.master_brands') . ".name as brand_name"
            ])->join(
                Config::get('tables.master_brands'),
                Config::get('tables.master_table') . ".brand",
                "=",
                Config::get('tables.master_brands') . ".value"
            )->where(Config::get('tables.master_table') . ".product_sku", $sku)
            ->ORWHERE(Config::get('tables.master_table') . ".product_sku_old", $sku)
            ->get();


        $d = null;
        foreach ($rows as $row) {
            if (
                strlen($row->cat_name_long) > 0
                && strlen($row->dept_name_long) > 0
            ) {

                $d = $row;
                break;
            }
        }

        if ($d != null || sizeof($product_details) == 0) {
            if (sizeof($product_details) >= 1) {
                $product_details = $product_details[0];
            }
            return  [
                "page_title" => $d->cat_name_long,
                "full_title" => $d->dept_name_long . " "  . $d->cat_name_short,
                "email_title" => $d->filter_label,
                "dept_name_long" => $d->dept_name_long,
                "cat_name_long" => $d->cat_name_long,
                "cat_name_short" => $d->cat_name_short,
                "cat_image" => $d->cat_image,
                "description" => "Search hundreds of " . $d->cat_name_long  . " from top brands at once. Add to your room designs with your own design boards.",
                "image_url" => Product::$base_siteurl . $d->cat_image,
                "product_name" => $product_details->product_name,
                "brand" => $product_details->brand_name
            ];
        }

        return [];
    }


    public static function get_product_details($sku)
    {
        $user = Auth::user();
        $product_inventory_details = Inventory::get_product_from_inventory($user, $sku);
        $is_wishlisted = Wishlist::is_wishlisted($user, $sku);

        // check if product needs to be redirected
        $redirection = Product::is_redirect($sku);
        $prod = Product::where('product_sku', $sku)->orwhere('product_sku_old', $sku)
            ->join("master_brands", "master_data.brand", "=", "master_brands.value")
            ->get()->toArray();
        if (!isset($prod[0])) {
            return ["message" => "SKU " . $sku . " NOT FOUND"];
        }

        if ($redirection != null) {

            $redirection_sku = $redirection->redirect_sku;
            if ($redirection_sku != null) {
                $redirect_url = env('APP_URL') . "/product/" . $redirection_sku;
                $data = DB::table("master_data")

                    ->select(['product_name', 'min_price as price', 'min_was_price as was_price', 'main_product_images'])
                    ->where("product_sku", $redirection_sku)
                    ->get();
                if (!isset($data[0]))
                    return ["message" => "SKU " . $redirection_sku . " NOT FOUND"];
                $data = $data[0];
            } else
                $redirect_url = null;

            $prod_table = isset(Brands::$brand_mapping[$redirection->brand]) ? Brands::$brand_mapping[$redirection->brand] : null;

            if ($prod_table != null) {
                $sku_col = in_array($redirection->brand, Brands::$product_id_brands) ? "product_id" : "product_sku";
                $product = (object) $prod[0];
                $variations_data = Product::get_variations($product, null, false);
                $product_details = (array) $product;

                // adding inventory object details to main product array
                $product_details = array_merge($product_details, $product_inventory_details);

                $product = Product::get_details((object) $product_details, $variations_data, false, $is_wishlisted);
                $product['redirect_url'] = $redirect_url;
                $product['redirect'] = true;

                if (isset($data)) {
                    $product['redirect_details']['name'] = $data->product_name;
                    $product['redirect_details']['price'] = $data->price;
                    $product['redirect_details']['was_price'] = $data->was_price;
                    $product['redirect_details']['main_image'] = env('APP_URL') . $data->main_product_images;
                }

                $res['product'] = $product;
                $res['seo_data'] = Product::product_seo($sku, $product_details['LS_ID']);
                return $res;
            } else {
                return [];
            }
        }

        $westelm_cache_data  = DB::table("westelm_products_skus")
            ->selectRaw("COUNT(product_id) AS product_count, product_id")
            ->groupBy("product_id")
            ->get();

        $westelm_variations_data = [];

        if (sizeof($westelm_cache_data) > 0) {
            foreach ($westelm_cache_data as $row) {
                $westelm_variations_data[$row->product_id] = $row->product_count;
            }
        }

        if (Product::$color_map == null) {
            // set color map data to be used in setting variations data
            $color_rows = DB::table("color_mapping")
                ->select("*")
                ->get();
            foreach ($color_rows as $key => $value) {
                Product::$color_map[strtolower($value->color_alias)] = [
                    'hexcode' => $value->color_hex,
                    'name' => $value->color_name
                ];
                Product::$color_map[strtolower($value->color_name)] = [
                    'hexcode' => $value->color_hex,
                    'name' => $value->color_name
                ];
            }
        }

        $westelm_cache_data = [];
        $variations = null;

        $variations_data = Product::get_variations((object)$prod[0], $westelm_variations_data, false);
        $prod[0] = (object) array_merge((array)$prod[0], $product_inventory_details);

        $is_wishlisted = Wishlist::is_wishlisted($user, $sku);

        /*
        $product,
        $variations,
        $is_listing_API_call = null,
        $isMarked = false,
        $isTrending = false,
        $is_details_minimal = false
         */
        return [
            "seo_data" => Product::product_seo($sku, $prod[0]->LS_ID),
            "product" => Product::get_details($prod[0], $variations_data, false, $is_wishlisted),
            "upgrades" => Product::product_upgrades($user,$sku),
        ];
    }

    // sends unique filter values.
    public static function get_all_variation_filters($sku)
    {
        $var = DB::table("westelm_products_skus")
            ->where("product_id", $sku)
            //->limit(20)
            ->get();
        $variation_filters = [];

        foreach ($var as $prod) {
            for ($i = 1; $i <= 6; $i++) {
                $col = "attribute_" . $i;
                $str = $prod->$col;
                $str_exp = explode(":", $str);
                if (isset($str_exp[0]) && isset($str_exp[1])) {
                    //echo $filter_key . "<br>";

                    $str_exp[0] = preg_replace('/please|Please|select|Select/', '', $str_exp[0]);
                    // $filter_key = Product::get_filter_key($str_exp[0]);
                    $filter_key = $col;
                    $features[$filter_key] = $str_exp[1];

                    // setting array indexes for each filter category
                    if (!isset($variation_filters[$filter_key]))
                        $variation_filters[$filter_key] = [];

                    // saving unique data values for the filter value display

                    $found = false;
                    //echo sizeof($variation_filters[$filter_key]);
                    foreach ($variation_filters[$filter_key] as $filter) {
                        //echo "comparing " . $filter["value"] . " %% " . $str_exp[1] . "<br>";
                        if (isset($filter["name"])) {
                            if ($filter["name"] == $str_exp[1]) {
                                $found = true;
                                break;
                            }
                        }
                    }

                    if (!$found) {

                        array_push($variation_filters[$filter_key], [
                            "label" => $str_exp[0],
                            "name" => $str_exp[1],
                            "value" => strtolower(preg_replace("' '", "-", $str_exp[1])),
                            "enabled" => true
                        ]);
                    }
                }
            }
        }

        return $variation_filters;
    }


    public static function mark_image($product_sku, $image, $col)
    {

        $img_files = DB::table('master_data')
            ->select([$col])
            ->where('product_sku', $product_sku)
            ->get();

        if (isset($img_files[0])) {
            $file_paths = $img_files[0]->$col;
            $file_paths = explode(",", $file_paths);

            if (!in_array($image, $file_paths)) {
                $file_paths[] = $image;
                $file_paths_all = implode(",", $file_paths);

                return DB::table('master_data')
                    ->where('product_sku', $product_sku)
                    ->update([$col => $file_paths_all]);
            }
        }

        return false;
    }
    /**
     * Same as above function just changing name of table to master_new
     */
    public static function mark_image_master_new($product_sku, $image, $col)
    {

        $img_files = DB::table('master_new')
            ->select([$col])
            ->where('product_sku', $product_sku)
            ->get();

        if (isset($img_files[0])) {
            $file_paths = $img_files[0]->$col;
            $file_paths = explode(",", $file_paths);

            if (!in_array($image, $file_paths)) {
                $file_paths[] = $image;
                $file_paths_all = implode(",", $file_paths);

                return DB::table('master_new')
                    ->where('product_sku', $product_sku)
                    ->update([$col => $file_paths_all]);
            }
        }

        return false;
    }

    public static function get_selected_products($sku_array)
    {
        $product_rows = Product::whereIn('product_sku', $sku_array)->get();
        $response = [];

        $variations = null;
        $is_listing_API_call = true;
        $isMarked = false;
        $is_details_minimal = false;

        foreach ($product_rows as $product) {
            $response[] = Product::get_details($product, $variations, $is_listing_API_call, $isMarked, false, $is_details_minimal);
        }

        return $response;
    }

    public static function get_userproduct_list($sku)
    {
        $response_user = [];
        $response_product = [];
        $response_user_str = '';
        $response_sku_str = '';
        $response = [];
        $uid = 0;

        $is_authenticated = Auth::check();
        if ($is_authenticated) {
            $user = Auth::user();

            $uid = $user->id;
        }

        //	$uid = 511;	
        //	$uid = 511;	
        //	$uid = 511;	
        $user_rows = DB::table('user_views')
            ->select('user_id')
            ->distinct()
            ->where('product_sku', $sku)
            ->where('user_id', '!=', $uid)
            ->get();


        $main_product_LSID = $product_rows = DB::table('master_data')
            ->select(['LS_ID'])
            ->where('product_sku', $sku)
            ->get();


        $main_LSID = explode(",", $main_product_LSID[0]->LS_ID);

        $LSID = $main_LSID[0];

        if (isset($user_rows)) {
            foreach ($user_rows as $ur) {
                $response_user_str = $response_user_str . "," . $ur->user_id;
            }
            $response_user_str = ltrim($response_user_str, ',');
            $user_array = explode(",", $response_user_str);

            $product_sku_rows = DB::table('user_views')
                ->select('product_sku')
                ->whereIn('user_id', $user_array)
                ->where('product_sku', '!=', $sku)
                ->where('user_id', '!=', $uid)
                ->get();


            $main_LSID = explode(",", $main_product_LSID[0]->LS_ID);

            $LSID = $main_LSID[0];

            if (isset($user_rows)) {
                foreach ($user_rows as $ur) {
                    $response_user_str = $response_user_str . "," . $ur->user_id;
                }
                $response_user_str = ltrim($response_user_str, ',');
                $user_array = explode(",", $response_user_str);

                $product_sku_rows = DB::table('user_views')
                    ->select('product_sku')
                    ->whereIn('user_id', $user_array)
                    ->where('product_sku', '!=', $sku)
                    ->where('user_id', '!=', $uid)
                    ->get();



                if (isset($product_sku_rows)) {
                    foreach ($product_sku_rows as $pr) {
                        $response_sku_str = $response_sku_str . "," . $pr->product_sku;
                    }
                    $response_sku_str = ltrim($response_sku_str, ',');
                    $sku_array = explode(",", $response_sku_str);


                    $product_rows = DB::table('master_data')
                        ->whereIn('master_data.product_sku', $sku_array)
                        ->where('master_data.product_status', 'active')
                        ->join('user_views', 'user_views.product_sku', '=', 'master_data.product_sku')
                        ->join('master_brands', 'master_brands.value', '=', 'master_data.brand')
                        ->select(['master_data.id', 'master_data.product_description', 'master_data.product_status', 'master_data.product_name', 'master_data.product_sku', 'master_brands.name as brand_name', 'master_data.min_price as price', 'master_data.min_was_price as was_price', 'master_data.main_product_images as image', 'master_data.LS_ID', DB::raw('count(user_views.user_id) as viewers')]) //,'user_views.updated_at as last_visit','user_views.num_views as visit_count'
                        //->select(['master_data.product_name', 'master_data.product_sku', 'master_brands.name as brand_name', 'master_data.min_price as price', 'master_data.main_product_images as image', 'master_data.LS_ID']) 
                        ->groupBy('user_views.product_sku')
                        ->orderBy(\DB::raw('count(user_views.user_id)'), 'DESC')
                        ->get();

                    if (strlen($LSID) == 3) {
                        $response = Product::get_product_for_three_digit($product_rows, $LSID);
                    } else {
                        $response = Product::get_product_for_four_digit($product_rows, $LSID);
                    }
                }
            } else {
                // No User found
            }


            return $response;
        }
    }
    public static function get_product_for_three_digit($product_rows, $LSID)
    {



        $response = [];
        $response_nmatch = [];
        $response_match = [];
        $response_match1 = [];
        $response_deptsame = [];
        $response_deptother = [];
        $response_catsame = [];
        $response_catother = [];
        $response_catdeptsame = [];
        $response_catdeptother = [];


        /* ================== Sort By Identical Start =========================== */

        foreach ($product_rows as $product) {
            $product->image =  env('APP_URL') . $product->image;

            $LS_ID_arr = explode(",", $product->LS_ID);
            //$LS_ID_arr = explode(",",$product['LS_ID']);

            if (count($LS_ID_arr) == 1) {
                if ($LS_ID_arr[0] == $LSID) {
                    array_push($response_match, $product);
                } else {
                    array_push($response_nmatch, $product);
                }
            } else {

                if (in_array($LSID, $LS_ID_arr)) {
                    array_push($response_match1, $product);
                } else {
                    array_push($response_nmatch, $product);
                }
            }
        }
        $response_match = array_merge($response_match, $response_match1);


        /* ================== Sort By Identical End =========================== */




        /* ================== Sort By Category+Department Start =========================== */

        foreach ($response_nmatch as $catdept) {
            $flag = 0;
            $LS_ID_arr = explode(",", $catdept->LS_ID);
            //$LS_ID_arr = explode(",",$catdept['LS_ID']);


            for ($i = 0; $i < count($LS_ID_arr); $i++) {

                if (substr($LS_ID_arr[$i], 0, 2) == $LSID[0] . $LSID[1]) {
                    $flag = 1;
                    break;
                } else {
                    $flag = 0;
                }
            }
            if ($flag == 1) {
                array_push($response_catdeptsame, $catdept);
            } else {
                array_push($response_catdeptother, $catdept);
            }
        }
        /* ================== Sort By Category+Department End =========================== */



        /* ================== Sort By Category Start =========================== */

        foreach ($response_catdeptother as $cat) {
            $flag = 0;

            $LS_ID_arr = explode(",", $cat->LS_ID);
            //$LS_ID_arr = explode(",",$cat['LS_ID']);

            for ($i = 0; $i < count($LS_ID_arr); $i++) {


                if (substr($LS_ID_arr[$i], 1, 1) == $LSID[1]) {
                    $flag = 1;
                    break;
                } else {
                    $flag = 0;
                }
            }

            if ($flag == 1) {
                array_push($response_catsame, $cat);
            } else {
                array_push($response_catother, $cat);
            }
        }
        /* ================== Sort By Category End =========================== */




        /* ================== Sort By Department Start =========================== */

        foreach ($response_catother as $dept) {
            $flag = 0;
            $LS_ID_arr = explode(",", $dept->LS_ID);
            //$LS_ID_arr = explode(",",$dept['LS_ID']);

            for ($i = 0; $i < count($LS_ID_arr); $i++) {


                if (substr($LS_ID_arr[$i], 0, 1) == $LSID[0]) {
                    $flag = 1;
                    break;
                } else {
                    $flag = 0;
                }
            }

            if ($flag == 1) {
                array_push($response_deptsame, $dept);
            } else {
                array_push($response_deptother, $dept);
            }
        }

        /* ================== Sort By Department End =========================== */






        $response = array_values(array_merge($response_match, $response_catdeptsame, $response_catsame, $response_deptsame, $response_deptother));
        $response = array_slice($response, 0, 30);
        $i=0;
        $resultset = [];
        foreach ($response as $res){
            $resultset[$i] = [
                'product_name'=>$res->product_name,
                'product_sku'=>$res->product_sku,
                'brand_name'=>$res->brand_name,
                'price'=>$res->price,
                'image'=>$res->image,

            ];
            $i++;
        }
        return $resultset;
    }




    public static function get_product_for_four_digit($product_rows, $LSID)
    {

        $response = [];
        $response_nmatch = [];
        $response_match = [];
        $response_match1 = [];
        $response_deptsame = [];
        $response_deptother = [];
        $response_catsame = [];
        $response_catother = [];
        $response_identical = [];
        $remainarr = [];

        foreach ($product_rows as $pr) {
            $pr->image =  env('APP_URL') . $pr->image;
            $LS_ID_arr = explode(',', $pr->LS_ID);
            //$LS_ID_arr = explode(',',$pr['LS_ID']); 


            if (count($LS_ID_arr) == 1) {
                if ($LS_ID_arr[0] == $LSID) {
                    array_push($response_identical, $pr);
                } else {
                    array_push($response_catother, $pr);
                }
            } else {

                if (in_array($LSID, $LS_ID_arr)) {
                    array_push($response_match1, $pr);
                } else {
                    array_push($response_catother, $pr);
                }
            }
        }
        $response_identical = array_merge($response_identical, $response_match1);

        /* if(in_array($LSID, $LS_ID_arr)){	
				array_push($response_identical,$pr);
			}
			else{
					array_push($response_catother,$pr);
			}
			*/


        $LSID_dept = $LSID[0] . $LSID[1] . $LSID[2];


        /* ================== Sort By Department Start =========================== */

        foreach ($response_catother as $dept) {
            $flag = 0;
            $LS_ID_arr = explode(",", $dept->LS_ID);
            //$LS_ID_arr = explode(",",$dept['LS_ID']);

            for ($i = 0; $i < count($LS_ID_arr); $i++) {
                if ((substr($LS_ID_arr[$i], 0, 3)) ==  $LSID_dept) {
                    $flag = 1;
                    break;
                } else {
                    $flag = 0;
                }
            }

            if ($flag == 1) {
                array_push($response_deptsame, $dept);
            } else {
                array_push($response_deptother, $dept);
            }
        }

        


        /* ================= User View Count Matching End ========================== */


        //$response = array_values(array_merge($response_identical, $response_deptsame, $response_nmatch));
        $response = array_values(array_merge($response_identical, $response_deptsame, $response_deptother));
        $response = array_slice($response, 0, 30);

        $i=0;
        $resultset = [];
        foreach ($response as $res){
            $resultset[$i] = [
                'product_name'=>$res->product_name,
                'product_sku'=>$res->product_sku,
                'brand_name'=>$res->brand_name,
                'price'=>$res->price,
                'image'=>$res->image,

            ];
            $i++;
        }
        return $resultset;
    }

    public static function get_new_sku($sku){
        $new_sku = DB::table('master_data')
                    ->select('product_sku')
                    ->where('product_sku_old', '=', $sku)
                    ->get(); 

        if(isset($new_sku) && isset($new_sku[0]->product_sku) ){
            return $new_sku[0]->product_sku;
        } 
        return false;           
    }

    public static function product_upgrades($user,$product_sku){
        // Get list of products user can also add 
        // From Product Details Page
        $arr = [];
        $upgrade_rows = DB::table('master_data')
                    ->select('upgrades')
                    ->where('product_sku', '=', $product_sku)
                    ->get(); 

        if(isset($upgrade_rows) && isset($upgrade_rows[0]->upgrades) ){
                $upgrade_arr = explode(',',$upgrade_rows[0]->upgrades);
                $inventory_rows = DB::table('lz_inventory')
                    ->select('*')
                    ->where('is_active','1')
                    ->whereRaw('parent_sku IN (' .$upgrade_rows[0]->upgrades.') OR product_sku IN ('. $upgrade_rows[0]->upgrades.')')
                    ->get();

                if(isset($upgrade_rows)){
                    foreach($inventory_rows as $row){

                        $data_rows = DB::table('master_data')
                        ->select(['master_data.product_name','master_data.main_product_images','master_brands.name'])
                        ->join("master_brands", "master_data.brand", "=", "master_brands.value")
                        ->WHERE('product_sku' ,$row->product_sku)
                        ->ORWHERE('product_sku' ,$row->parent_sku)
                        ->get();
 
                        $product_set_inventory_details = Inventory::get_product_from_inventory($user, $row->product_sku);
                        $price = $was_price = null;
                        if ($product_set_inventory_details['in_inventory']) {
                            $price = $product_set_inventory_details['inventory_product_details']['price'];
                            $was_price = $product_set_inventory_details['inventory_product_details']['was_price'];
                        }
                        $set = [
                            'parent_sku' => $row->parent_sku,
                            'sku' => $row->product_sku,
                            'name' => $data_rows[0]->product_name,
                            'brand' => $data_rows[0]->name,
                            'image' => env('APP_URL') . $data_rows[0]->main_product_images,
                            //'link' => $row->product_url,
                            'price' => isset($price) ? $price : $row->price,
                            'was_price' => isset($was_price) ? $was_price : $row->was_price
                        ];

                        $set = array_merge($set,$product_set_inventory_details );

                        array_push($arr, $set);
                    }
                }
                

        }
        return $arr;
    }
};