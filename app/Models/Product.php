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
        $new_products_only  = filter_var(Input::get('new'), FILTER_VALIDATE_BOOLEAN);
        $sale_products_only = filter_var(Input::get('sale'), FILTER_VALIDATE_BOOLEAN);
        $is_details_minimal = filter_var(Input::get('board-view'), FILTER_VALIDATE_BOOLEAN);
        $is_best_seller     = filter_var(Input::get('bestseller'), FILTER_VALIDATE_BOOLEAN);
        $is_admin_call = filter_var(Input::get('admin'), FILTER_VALIDATE_BOOLEAN);



        $all_filters = [];
        $query       = DB::table('master_data')->where('product_status', 'active');

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

        $query = $query->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');
        $query = DimensionsFilter::apply($query, $all_filters);
        $query = CollectionFilter::apply($query, $all_filters);
        $query = MaterialFilter::apply($query, $all_filters);
        $query = FabricFilter::apply($query, $all_filters);
        $query = DesignerFilter::apply($query, $all_filters);
        $query = MFDCountry::apply($query, $all_filters);



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
            if ($sale_products_only == false && !$new_products_only)
                $query = $query->orderBy('serial', 'asc');
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
            $date_four_weeks_ago = date('Y-m-d', strtotime('-60 days'));
            $query = $query->whereRaw("created_date >= '" . $date_four_weeks_ago . "'");
            $query = $query->orderBy('new_group', 'asc');
        }

        // for getting products on sale
        if ($sale_products_only == true) {
            $query = $query->whereRaw('price >  0')
                ->whereRaw('was_price > 0')
                ->whereRaw('convert(was_price, unsigned) > convert(price, unsigned)')
                ->orderBy(DB::raw("`price` / `was_price`"), 'asc');
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

        $a = Product::get_product_obj($query->get(), $all_filters, $dept, $cat, $subCat, $is_listing_API_call, $is_details_minimal, $is_admin_call);

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
    public static function get_all_dept_category_filter($brand_name = null, $all_filters)
    {
        $in_filter_categories = $all_filters['category'];
        $LS_IDs = DB::table("master_data")
            ->select("LS_ID");

        if ($brand_name !== null) $LS_IDs = $LS_IDs->where("brand", $brand_name);

        // all all new filters here
        $LS_IDs = Filters::apply(null, null, $all_filters, $LS_IDs, Config::get('meta.FILTER_ESCAPE_CATEGORY'));

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
        foreach ($LS_IDs as $LS_ID) {
            $IDs = explode(",", $LS_ID->LS_ID);
            foreach ($IDs as $ID) {
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

    public static function get_seating_filter($dept, $cat, $all_filters)
    {

        $all_seating = [];
        $rows = DB::table("filter_map_seating")->get();
        $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);
        $products = DB::table("master_data")
            ->selectRaw("count(product_name) AS products, seating");
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

        $seating_holder = [];

        foreach ($all_seating as $name => $value) {
            array_push($seating_holder, $value);
        }

        return $seating_holder;
    }

    public static function get_shape_filter($dept, $cat, $all_filters)
    {

        $all_shapes = [];
        $rows = DB::table("master_data")->whereRaw('shape IS NOT NULL')->whereRaw("LENGTH(shape) > 0")->distinct()->get(['shape']);
        $LS_IDs = Product::get_dept_cat_LS_ID_arr($dept, $cat);
        $products = DB::table("master_data")
            ->selectRaw("count(product_name) AS products, shape");


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

        $shapes_holder = [];

        foreach ($all_shapes as $name => $value) {
            array_push($shapes_holder, $value);
        }

        return $shapes_holder;
    }

    public static function get_brands_filter($dept, $cat, $all_filters)
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

    public static function get_price_filter($dept, $cat, $all_filters)
    {

        $p_to = $p_from = null;

        $price = DB::table('master_data');
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

            if ($p_from == 0) $p_from = $min;
            if ($p_to == 0) $p_to = $max;

            return [
                "from" => round($p_from),
                "to" => round($p_to),
                "max" => isset($max) ? $max : 0,
                "min" => isset($min) ? $min : 0,
            ];
        }
    }

    public static function get_color_filter($dept, $cat, $subCat, $all_filters, $request_colors)
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
            ->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"');

        $products = DimensionsFilter::apply($products, $all_filters);
        $products = CollectionFilter::apply($products, $all_filters);
        $products = MaterialFilter::apply($products, $all_filters);
        $products = FabricFilter::apply($products, $all_filters);
        $products = DesignerFilter::apply($products, $all_filters);
        $products = MFDCountry::apply($products, $all_filters);


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

        if (sizeof($all_filters) > 0) {

            if (
                isset($all_filters['brand'])
                && strlen($all_filters['brand'][0]) > 0
            ) {
                $products = $products->whereIn('brand', $all_filters['brand']);
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

    public static function get_product_type_filter($dept, $category, $subCat, $all_filters)
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
            'colorFilter' => Product::get_color_filter($dept, $category, $subCat, $all_filters, $color_filter),
            'productTypeFilter' => $arr
        ];
    }

    public static function get_product_obj($products, $all_filters, $dept, $cat, $subCat, $is_listing_API_call = null, $is_details_minimal = false)
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

        $brand_holder = Product::get_brands_filter($dept, $cat, $all_filters);
        $price_holder = Product::get_price_filter($dept, $cat, $all_filters);
        $product_type_holder = Product::get_product_type_filter($dept, $cat, $subCat, $all_filters)['productTypeFilter'];
        $color_filter = Product::get_product_type_filter($dept, $cat, $subCat, $all_filters)['colorFilter'];

        $seating_filter = Product::get_seating_filter($dept, $cat, $all_filters);
        $shape_filter = Product::get_shape_filter($dept, $cat, $all_filters);

        if ($dept == "all") {
            if (!isset($all_filters['category']))
                $all_filters['category'] = [];

            $brand_filter = isset($all_filters['brand'][0]) ? $all_filters['brand'][0] : null;
            $category_holder =  Product::get_all_dept_category_filter($brand_filter, $all_filters);
        }

        $dimension_filter = DimensionsFilter::get_filter($dept, $cat, $all_filters);
        $filter_data = [
            "brand"  => $brand_holder,
            "price"  => $price_holder,
            "type" => $product_type_holder,
            "color" => $color_filter,
            "category" => $dept == "all" ? $category_holder : null,
            "shape" => $shape_filter,
            "seating" => $seating_filter,
            "height" => [$dimension_filter['dim_height']],
            "width" => [$dimension_filter['dim_width']],
            "length" => [$dimension_filter['dim_length']],
            "diameter" => [$dimension_filter['dim_diameter']],
            "square" => [$dimension_filter['dim_square']],
            "depth" => [$dimension_filter['dim_depth']],
            "collection" => isset($all_filters['collection']) ? $all_filters['collection'] : null,
            "material" => MaterialFilter::get_filter_data($dept, $cat, $all_filters),
            "fabric" => FabricFilter::get_filter_data($dept, $cat, $all_filters),
            "designer" => DesignerFilter::get_filter_data($dept, $cat, $all_filters),
            "country" => MFDCountry::get_filter_data($dept, $cat, $all_filters),
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

        $p_price = str_replace("$", "", $product->price);
        $wp_price = str_replace("$", "", $product->was_price);

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


        // for wishlist
        $data =  [
            //'id'               => isset($product->id) ? $product->id : rand(1, 10000) * rand(1, 10000),
            'sku'              => $product->product_sku,
            'is_new'           => $is_new,
            'redirect'         => isset($product->redirect) ? $product->redirect : false,
            'in_inventory'     => isset($product->in_inventory) ? $product->in_inventory : false,
            'inventory_product_details' => isset($product->inventory_product_details) ? $product->inventory_product_details : null,
            //    'sku_hash'         => $product->sku_hash,
            'site'             => $product->name,
            'name'             => $product->product_name,
            'product_url'      => urldecode($product->product_url),
            'product_detail_url' => Product::$base_siteurl . "/product/" . $product->product_sku,
            'is_price'         => Utility::rm_comma($product->price),
            'was_price'        => Utility::rm_comma($product->was_price),
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
            'product_care'       => isset($product->product_care) ? $product->product_care : null
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
                    if (isset($variations[$i]['image'])) {
                        if ($variations[$i]['image'] === Product::$base_siteurl) {
                            $variations[$i]['image'] = $data['main_image'];
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

    public static function get_westelm_variations($product, $wl_v, $is_listing_API_call = null, $brand = 'westelm')
    {
        $cols = $brand == 'westelm' ? Config::get('meta.westelm_variations_cols') : Config::get('meta.' . $brand . '_variations_cols');
        $variation_table = $brand == 'westelm' ? Config::get('tables.variations.westelm.table') : Config::get('tables.variations.' . $brand . '.table');
        $attr_count = $brand == 'westelm' ? 6 : 3;

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
                    $excluded_options = ['color', 'fabric'];
                    foreach ($extras_key as $key => $arr) {
                        if (sizeof($arr) > 4) {
                            $is_dropdown = true;
                        }

                        $select_type = in_array($key, $multi_select_filters) ? "multi_select" : "single_select";
                        $select_type = in_array($key, $excluded_options) ? "excluded" : $select_type;

                        $select_type = ($key == 'color' && Utility::match_exclude_LDIS($product->LS_ID)) ? "excluded" : $select_type;

                        if ($key == "color") {
                            $extras["color_group"] = [
                                'select_type' => $select_type,
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

                    $variation_extras = $extras;

                    array_push($variations, [
                        "product_sku" => $product->product_sku,
                        "variation_sku" => $prod->sku,
                        "name" => $name,
                        "features" => $features,
                        "has_parent_sku" => isset($prod->has_parent_sku) ? (bool) $prod->has_parent_sku : false,
                        "image" => Product::$base_siteurl . $prod->image_path,
                        "link" =>  "/product/" . $product->product_sku,
                        "swatch_image" => strlen($prod->swatch_image_path) != 0 ? Product::$base_siteurl . $prod->swatch_image_path : null,
                        "price" => $prod->price,
                        "was_price" => $prod->was_price
                    ]);
                }


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

        $variation = [];
        switch ($product->site_name) {
            case 'cb2':
                $variations =  Product::get_westelm_variations($product, $wl_v, $is_listing_API_call, $product->site_name);
                break;
            case 'cab':
                $variations = Product::get_westelm_variations($product, $wl_v, $is_listing_API_call, $product->site_name);
                break;
                break;
            case 'pier1':
                $variations = Product::get_pier1_variations($product);
                break;
            case 'westelm':
                $variations = Product::get_westelm_variations($product, $wl_v, $is_listing_API_call, $product->site_name);
                break;
            default:
                $variations = [];
                break;
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
        $prod = Product::where('product_sku', $sku)
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
                    ->select(['product_name', 'price', 'was_price', 'main_product_images'])
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
            "product" => Product::get_details($prod[0], $variations_data, false, $is_wishlisted)
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
			
		 $user_rows = DB::table('user_views')
            ->select('user_id')
			->distinct()
            ->where('product_sku', $sku)
			->where('user_id','!=', $uid)
            ->get();
 
		$main_product_LSID = $product_rows = DB::table('master_data')
				->select(['LS_ID'])
				->where('product_sku', $sku)  
				->get();
				
				
		$main_LSID = explode(",",$main_product_LSID[0]->LS_ID) ;		
				
		$LSID = $main_LSID[0];	
       
		if(isset($user_rows)){
			foreach ($user_rows as $ur) {  
			  $response_user_str = $response_user_str.",".$ur->user_id;
			}
			$response_user_str = ltrim($response_user_str, ',');
			$user_array = explode(",",$response_user_str);
			
		 	$product_sku_rows = DB::table('user_views')
            ->select('product_sku')
            ->whereIn('user_id',$user_array)  
			->where('product_sku', '!=', $sku)
            ->get();
			
			
			
			if(isset($product_sku_rows)){
				foreach ($product_sku_rows as $pr) {  
				  $response_sku_str = $response_sku_str.",".$pr->product_sku;
				   
				}
				$response_sku_str = ltrim($response_sku_str, ',');
				$sku_array = explode(",",$response_sku_str);
				
				
				$product_rows = DB::table('master_data') 
				->whereIn('master_data.product_sku', $sku_array)  
				->where('master_data.product_status','active') 
				->join('user_views', 'user_views.product_sku', '=', 'master_data.product_sku')	
				->join('master_brands', 'master_brands.value', '=', 'master_data.brand')
				->select(['master_data.id','master_data.product_description','master_data.product_status','master_data.product_name','master_data.product_sku','master_brands.name as brand_name','master_data.price','master_data.was_price','master_data.main_product_images as image','master_data.LS_ID',DB::raw('count(user_views.user_id) as viewers')])//,'user_views.updated_at as last_visit','user_views.num_views as visit_count'
				->groupBy('user_views.product_sku')
				->orderBy(\DB::raw('count(user_views.user_id)'), 'DESC')	
				->get(); 
				
			    if(strlen($LSID)==3){
						$response = Product::get_product_for_three_digit($product_rows,$LSID);
				}
				else{
						$response = Product::get_product_for_four_digit($product_rows,$LSID);
				}
			} 
		}
		else{
		      // No User found
		}
		

        return $response;
    }
	
	
	public static function get_product_for_three_digit($product_rows,$LSID){ 
	
 
	
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
			       $product->image =  env('APP_URL').$product->image; 
					 
					$LS_ID_arr = explode(",",$product->LS_ID);
				//$LS_ID_arr = explode(",",$product['LS_ID']);
					
					if(count($LS_ID_arr)==1){ 
						if($LS_ID_arr[0]==$LSID){
							array_push($response_match,$product); 
						}
						else{
							array_push($response_nmatch,$product);
						} 
					}
					
					else {  
					
							if(in_array($LSID,$LS_ID_arr))		
							{
								array_push($response_match1,$product);
							}
							else{
								   array_push($response_nmatch,$product);
							}
						
					}
					
					
					
				} 
				$response_match = array_merge($response_match,$response_match1);
				
				  
		        /* ================== Sort By Identical End =========================== */ 
				
				
				
				
				/* ================== Sort By Category+Department Start =========================== */   
				
				foreach($response_nmatch as $catdept){ 
					$flag =0; 
					$LS_ID_arr = explode(",",$catdept->LS_ID);
					//$LS_ID_arr = explode(",",$catdept['LS_ID']);
					
				 
					for($i=0;$i<count($LS_ID_arr);$i++){
						
						if(substr($LS_ID_arr[$i], 0, 2)==$LSID[0].$LSID[1]){ 
							$flag = 1;
							break;
						}
						else{
							   $flag = 0; 
						} 
				
					}
					if($flag==1){
						array_push($response_catdeptsame,$catdept);
					}
					else{
						array_push($response_catdeptother,$catdept);
					}
				
					
				 
				
				} 
				/* ================== Sort By Category+Department End =========================== */   
				
				
				 
				/* ================== Sort By Category Start =========================== */   
				
				foreach($response_catdeptother as $cat){
					$flag = 0; 
				
					$LS_ID_arr = explode(",",$cat->LS_ID);
					//$LS_ID_arr = explode(",",$cat['LS_ID']);
					
					for($i=0;$i<count($LS_ID_arr);$i++){
						 
						
						if(substr($LS_ID_arr[$i], 1, 1)==$LSID[1]){ 
							$flag = 1;
							break;
						}
						else{
							   $flag = 0; 
						} 
					}
					
					if($flag==1){
						array_push($response_catsame,$cat);
					}
					else{
						array_push($response_catother,$cat);
					}
				
				} 
				/* ================== Sort By Category End =========================== */   
				
				 
				
				
				/* ================== Sort By Department Start =========================== */  
				   
				foreach($response_catother as $dept){
					$flag = 0; 
					$LS_ID_arr = explode(",",$dept->LS_ID);
					//$LS_ID_arr = explode(",",$dept['LS_ID']);
					
					for($i=0;$i<count($LS_ID_arr);$i++){
					 
						
						if(substr($LS_ID_arr[$i], 0, 1)==$LSID[0]){ 
							$flag = 1;
							break;
						}
						else{
							   $flag = 0; 
						} 
					}
					
					if($flag==1){
						array_push($response_deptsame,$dept);
					}
					else{
						array_push($response_deptother,$dept);
					}
				
				}
				
				/* ================== Sort By Department End =========================== */  
				
				
				
				
				
				
				$response = array_values(array_merge($response_match, $response_catdeptsame, $response_catsame, $response_deptsame, $response_deptother));
				$response = array_slice($response,0,30);
				return $response;
	}
	
 

	public static function get_product_for_three_digit_old($product_rows,$LSID){
		/*	$product_rows=array (
  0 => 
  array (
    'id' => 36317,
    'product_description' => 'A great way to add some chic vibes to your shelving, the Jordan Shelf Brackets are both bold and functional.
',
    'product_status' => 'active',
    'product_name' => 'Jordan Shelf Brackets (Set of 2)',
    'product_sku' => 'jordan-shelf-brackets-set-of-2-brass-d7812',
    'brand_name' => 'West Elm',
    'price' => '40',
    'was_price' => '40',
    'image' => '/westelm/westelm_images/jordan-shelf-brackets-set-of-2-brass-d7812_main.jpg',
    'LS_ID' => '304,542,507',
    'viewers' => 98,
  ),
  1 => 
  array (
    'id' => 36019,
    'product_description' => 'With its walnut finish and nod towards mid-century styling, the Ansel Bed will
lend a feeling of grandness to your bedroom. Even better, four side drawers
add discrete functional storage space to stash away bedding, clothes and
shoes, helping you stay clutter-free.

###### KEY DETAILS

  * Solid eucalyptus wood frame & legs.
  * Engineered wood headboard, footboard and drawers covered in real walnut wood veneer.
  * Covered in a water-based Walnut finish.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Four drawers within bed frame open on smooth metal glides.
  * Poplar wood slats included.
  * Available with or without headboard.
  * Compatible with West Elm upholstered headboards. 
  * Accommodates most standard mattresses; box spring is optional.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Ansel Side Storage Bed',
    'product_sku' => 'ansel-side-storage-bed-h4962',
    'brand_name' => 'West Elm',
    'price' => '1499-2099',
    'was_price' => '1499-2099',
    'image' => '/westelm/westelm_images/ansel-side-storage-bed-h4962_main.jpg',
    'LS_ID' => '551',
    'viewers' => 94,
  ),
  2 => 
  array (
    'id' => 51931,
    'product_description' => 'Sheer beauty. Designed by Ceci Thompson, modern brushed oak veneer frame takes on a Mad Men-esque vibe with fine wire mesh brass-plated doors. Rich and textured, mesh creates a peek-a-boo effect, ideal for concealing media devices without losing remote functionality. Four doors open to two adjustable shelves for plenty of storage. Wire mesh cabinet credenza leaves space behind both inner corners of shelves for media cords to snake down and out the back/center of the piece. Learn about Ceci Thompson on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Trace Brass Wire Mesh Cabinet Credenza',
    'product_sku' => '492239',
    'brand_name' => 'CB2',
    'price' => '1199',
    'was_price' => '1199',
    'image' => '/cb2/_images/main/trace-wire-mesh-cabinet-credenza.jpg',
    'LS_ID' => '533,534,99',
    'viewers' => 65,
  ),
  3 => 
  array (
    'id' => 50175,
    'product_description' => 'A minimalist, modern piece with statement-making style, our two-toned Dominick extension dining table sets a perfect stage for gourmet gatherings of all sizes. The espresso-brown tabletop contrasts subtly with the matte black legs for sophisticated visual interest. With two breadboard extensions that are designed to easily fit into place when you need more space, your entertaining mantra can truly be "the more, the merrier."',
    'product_status' => 'active',
    'product_name' => 'Two Tone Wood Dominick Extension Dining Table',
    'product_sku' => '566633',
    'brand_name' => 'World Market',
    'price' => '349.99',
    'was_price' => '699.99',
    'image' => '/nw/images/85156_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '552',
    'viewers' => 60,
  ),
  4 => 
  array (
    'id' => 36233,
    'product_description' => 'With its slim metal frame and minimalist design, this floor mirror adds a
neutral-yet-polished touch to bedrooms, entryways and beyond. It comes in a
variety of finishes so that you can pick the color that complements your
home\'s decor best.

###### KEY DETAILS

  * 30"w x 1.75"d x 72"h.
  * Mirrored glass.
  * Metal frame.
  * May be leaned against a wall; does not wall mount.
  * Tip kit included.
  * Safe for use in a residential bathroom.
  * Not tested for commercial use.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Metal Frame 72" Floor Mirror',
    'product_sku' => 'metal-floor-mirror-w446',
    'brand_name' => 'West Elm',
    'price' => '499',
    'was_price' => '499',
    'image' => '/westelm/westelm_images/metal-floor-mirror-w446_main.jpg',
    'LS_ID' => '1110',
    'viewers' => 54,
  ),
  5 => 
  array (
    'id' => 51922,
    'product_description' => 'Statement piece is an understatement. Rounded, chunky brass-finished legs intersect four doors clad in lacquered linen and sleek brass pulls. Glam credenza opens to two large shelves with middle divide and cord cut outs. Designed for an entry, dining or living room moment. Learn more about Ceci Thompson on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Dorset Linen Credenza',
    'product_sku' => '317352',
    'brand_name' => 'CB2',
    'price' => '1699',
    'was_price' => '1599',
    'image' => '/cb2/_images/main/dorset-linen-credenza.jpg',
    'LS_ID' => '534,99',
    'viewers' => 45,
  ),
  6 => 
  array (
    'id' => 35611,
    'product_description' => 'Made from richly-grained solid mango wood and supported by blackened steel
frames, our Industrial Modular Storage Collection combines form, function and
versatility. It offers plenty of storage and shelving space, while its
freestanding design means that you can easily pair it with other pieces in the
collection to create a set that\'s right for you. The natural variations in
mango wood make each piece subtly unique.

  * 17"sq. x 84"h.
  * Solid mango wood with natural color variations.
  * Blackened steel frames and legs.
  * Finished on all sides.  

  * The mango wood used on this product is sustainably sourced from trees that no longer produce fruit. 
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Industrial Modular 17" Open & Closed Storage',
    'product_sku' => 'rustic-modular-16-open-closed-storage-h964',
    'brand_name' => 'West Elm',
    'price' => '699-2796',
    'was_price' => '699-2796',
    'image' => '/westelm/westelm_images/rustic-modular-16-open-closed-storage-h964_main.jpg',
    'LS_ID' => '632',
    'viewers' => 44,
  ),
  7 => 
  array (
    'id' => 51296,
    'product_description' => 'The comfort of an upholstered leather headboard meets the handsome warmth of a
solid wood frame to create our Modern Show Wood Bed. Underneath it all, its
angled and elevated legs allow for under-the-bed storage.

###### KEY DETAILS

  * Solid eucalyptus wood frame in a Dark Walnut finish.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Saddle Leather headboard upholstery in Nut.
  * Accommodates most standard mattresses; box spring optional.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](http://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Modern Leather Show Wood Bed',
    'product_sku' => 'modern-leather-show-wood-bed-h6296',
    'brand_name' => 'West Elm',
    'price' => '1199-1399',
    'was_price' => '1199-1399',
    'image' => '/westelm/westelm_images/modern-leather-show-wood-bed-h6296_main.jpg',
    'LS_ID' => '340',
    'viewers' => 43,
  ),
  8 => 
  array (
    'id' => 35212,
    'product_description' => 'Don\'t let the Aston\'s retro silhouette fool you: This sofa is made for modern
lounging. Its down-wrapped back cushions offer sink-right-in comfort while the
two side pillows are naptime approved. It\'s durable too, with a frame made of
kiln-dried, sustainably sourced wood and covered in your choice of leather.

###### KEY DETAILS

  * 86.5"w x 35.5"d x 31.25"h.
  * Solid and engineered hardwood frame with reinforced joinery.
  * Solid rubberwood legs in a Walnut finish.
  * The wood used is kiln-dried for added durability.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Available in your choice of genuine top-grain leather or animal-friendly vegan leather.
  * Seat cushions have fiber-wrapped, high-resiliency polyurethane foam cores.
  * Seat firmness: Medium. On a scale from 1 to 5 (5 being firmest), we rate it a 3.
  * Back cushions are 70% poly fiber, 30% duck feather blend in down proof ticking.
  * Webbed seat and back support.
  * Loose, non-reversible cushions with zip-off covers.
  * Two side cushions included.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Aston Leather Sofa',
    'product_sku' => 'aston-leather-sofa-86-5-h4745',
    'brand_name' => 'West Elm',
    'price' => '2399-3199',
    'was_price' => '2399-3199',
    'image' => '/westelm/westelm_images/aston-leather-sofa-86-5-h4745_main.jpg',
    'LS_ID' => '201',
    'viewers' => 42,
  ),
  9 => 
  array (
    'id' => 51894,
    'product_description' => 'now you see it. Now you don\'t. This transparent new console floats in the room without taking up permanent visual residency. Thick-cut plexi in one seamless turn adds clean mod edge to entries, hallways, and behind the sofa. Priced to disappear quickly, too.',
    'product_status' => 'active',
    'product_name' => 'Peekaboo 38" Acrylic Console Table',
    'product_sku' => '103132',
    'brand_name' => 'CB2',
    'price' => '399',
    'was_price' => '379',
    'image' => '/cb2/_images/main/peekaboo-acrylic-console-table.jpg',
    'LS_ID' => '227,420,99',
    'viewers' => 41,
  ),
  10 => 
  array (
    'id' => 35976,
    'product_description' => 'The comfort of an upholstered headboard meets the handsome warmth of a solid
wood frame with angled mid-century lines to create our Modern Show Wood Bed.
Underneath it all, its angled and elevated legs allow for under-the-bed
storage.',
    'product_status' => 'active',
    'product_name' => 'Modern Show Wood Bed',
    'product_sku' => 'modern-show-wood-bed-pumice-yarn-dyed-linen-weave-h2922',
    'brand_name' => 'West Elm',
    'price' => '1099-1299',
    'was_price' => '1099-1299',
    'image' => '/westelm/westelm_images/modern-show-wood-bed-pumice-yarn-dyed-linen-weave-h2922_main.jpg',
    'LS_ID' => '300',
    'viewers' => 41,
  ),
  11 => 
  array (
    'id' => 35686,
    'product_description' => 'Brought to you in collaboration with Pottery Barn Kids, our GREENGUARD Gold
Certified Mid-Century Nursery Collection is made from sustainably sourced wood
that\'s kiln dried for extra strength, complete with child-safe, water-based
finishes. This crib grows with your baby, featuring two platform height
options, and can be converted into a cozy toddler bed with the matching
conversion kit (sold separately).',
    'product_status' => 'active',
    'product_name' => 'Mid-Century Convertible Crib - Acorn',
    'product_sku' => 'mid-century-convertible-crib-acorn-h3207',
    'brand_name' => 'West Elm',
    'price' => '149-599',
    'was_price' => '149-599',
    'image' => '/westelm/westelm_images/mid-century-convertible-crib-acorn-h3207_main.jpg',
    'LS_ID' => '942',
    'viewers' => 40,
  ),
  12 => 
  array (
    'id' => 51246,
    'product_description' => 'Our Remi Collection\'s low-slung style instantly makes for a brighter, airier
space. Compact and comfortable with a boxy, structured look, its high density
foam cushioning and spacious seats are meant for laidback lounging. It\'s
available in your choice of configuration too.

###### KEY DETAILS

  * Solid pine and engineered wood frame with reinforced joinery.
  * All wood is kiln dried for added durability.
  * Fully upholstered.
  * Plastic shadowline support legs in Black.
  * Frame padding made with polyurethane foam.
  * High-gauge sinuous springs provide cushion support.
  * Seat cushions have fiber-wrapped, high-resiliency polyurethane foam cores.
  * Seat firmness: Soft. On a scale from 1 to 5 (5 being firmest), we rate it a 2.
  * Back cushions have foam fill.
  * Collection is modular and can be arranged in your desired configuration.
  * Assembled in the USA.',
    'product_status' => 'active',
    'product_name' => 'Modular - Remi Sectional',
    'product_sku' => 'modular-remi-sectional-h6227',
    'brand_name' => 'West Elm',
    'price' => '499-849',
    'was_price' => '499-849',
    'image' => '/westelm/westelm_images/modular-remi-sectional-h6227_main.jpg',
    'LS_ID' => '202',
    'viewers' => 38,
  ),
  13 => 
  array (
    'id' => 51927,
    'product_description' => 'Alt metal. Bronze and gold textured brush strokes mosh across four doors of this handpainted mindi wood credenza. Designed by Mermelada Estudio, the faux-metal sheen brings edge to the piece, rock-solid on a wood plinth base. Learn more about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Kinzie Wood Credenza',
    'product_sku' => '630831',
    'brand_name' => 'CB2',
    'price' => '1199',
    'was_price' => '1199',
    'image' => '/cb2/_images/main/kinzie-wood-credenza.jpg',
    'LS_ID' => '534',
    'viewers' => 35,
  ),
  14 => 
  array (
    'id' => 35199,
    'product_description' => 'Our Slope Dining Chair curves in both the seat and back for extra comfort.
Available in your choice of leather with a timeless metal frame, this chair is
built to last, meeting commercial needs in addition to residential.',
    'product_status' => 'active',
    'product_name' => 'Slope Leather Dining Chair',
    'product_sku' => 'leather-slope-dining-chair-h1529',
    'brand_name' => 'West Elm',
    'price' => '269-758',
    'was_price' => '269-758',
    'image' => '/westelm/westelm_images/leather-slope-dining-chair-h1529_main.jpg',
    'LS_ID' => '510',
    'viewers' => 35,
  ),
  15 => 
  array (
    'id' => 51930,
    'product_description' => 'Eye catching. Designed by Mermelada Estudio and inspired by graphic tile design, rattan credenza spotlights a fresh twist on a classic form. Framed by solid mahogany, each of the four rattan-laid doors creates an optical effect, generating different patterns from different angles. Two adjustable shelves rest inside and square media cord cutouts on the back left and right offer hidden cord management. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Reflect Rattan Credenza',
    'product_sku' => '126995',
    'brand_name' => 'CB2',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/cb2/_images/main/reflect-rattan-credenza.jpg',
    'LS_ID' => '534',
    'viewers' => 34,
  ),
  16 => 
  array (
    'id' => 36236,
    'product_description' => 'With its subtle metal frame and minimalist design, this oversized floor mirror
adds a finished touch to any room.

  * 39"w x 1.3"d x 78"h.
  * Mirrored glass.
  * Metal frame in an Antique Brass finish.
  * May be leaned against a wall; does not wall mount.
  * Tip kit included.
  * Safe for use in a residential bathroom.
  * Not tested for commercial use.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Metal Frame Oversized 78" Floor Mirror, Antique Brass',
    'product_sku' => 'metal-frame-oversized-floor-mirror-h3370',
    'brand_name' => 'West Elm',
    'price' => '699',
    'was_price' => '699',
    'image' => '/westelm/westelm_images/metal-frame-oversized-floor-mirror-h3370_main.jpg',
    'LS_ID' => '1110',
    'viewers' => 34,
  ),
  17 => 
  array (
    'id' => 35672,
    'product_description' => 'Our Mid-Century 6-Drawer Dresser is a roomy storage solution that\'s built to
last. Made in a Fair Trade Certified™ facility, its sturdy frame is made from
kiln-dried, sustainably sourced wood and covered in water-based finishes.
Better yet: It\'s GREENGUARD Gold Certified as being low VOC.',
    'product_status' => 'active',
    'product_name' => 'Mid-Century 6-Drawer Dresser - Pebble',
    'product_sku' => 'mid-century-6-drawer-dresser-pebble-h3968',
    'brand_name' => 'West Elm',
    'price' => '1099',
    'was_price' => '1099',
    'image' => '/westelm/westelm_images/mid-century-6-drawer-dresser-pebble-h3968_main.jpg',
    'LS_ID' => '334',
    'viewers' => 34,
  ),
  18 => 
  array (
    'id' => 35688,
    'product_description' => 'Brought to you in collaboration with Pottery Barn Kids, our GREENGUARD Gold
Certified Mid-Century Nursery Collection is made from sustainably sourced wood
that\'s kiln dried for extra strength, complete with child-safe, water-based
finishes. This changing table has six roomy drawers and a removable topper
that keeps changing pads firmly in place.',
    'product_status' => 'active',
    'product_name' => 'Mid-Century 6-Drawer Changing Table - Acorn',
    'product_sku' => 'mid-century-6-drawer-changing-table-acorn--h3223',
    'brand_name' => 'West Elm',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/westelm/westelm_images/mid-century-6-drawer-changing-table-acorn--h3223_main.jpg',
    'LS_ID' => '933',
    'viewers' => 34,
  ),
  19 => 
  array (
    'id' => 34926,
    'product_description' => 'Inspired by the cool, clean lines of Scandinavian design, the Playa Collection
features a slim profile and modern finish. Made with FSC®-certified wood and
water-resistant cushions, this sectional features a loose, reversible chaise
that easily switches from left to right. It comes with a lounge chair and
spacious coffee table to make relaxing & entertaining even easier.

###### KEY DETAILS

  * Includes: 1 Playa 92" Reversible Sectional, 1 Playa Lounge Chair and 1 Playa Coffee Table.
  * Solid eucalyptus and mahogany wood frame.
  * All wood is kiln-dried and FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Covered in a water-based Mast finish.
  * Water-resistant cushions with 100% polyester covers in Cement (included).
  * Cushions feature polyurethane foam and polyester filling.
  * Loose, reversible cushions with zip-off covers.
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Playa Outdoor 92" Reversible Sectional, Lounge Chair & Coffee Table Set',
    'product_sku' => 'playa-outdoor-92-reversible-sectional-coffee-table-set-h5157',
    'brand_name' => 'West Elm',
    'price' => '1499',
    'was_price' => '1499',
    'image' => '/westelm/westelm_images/playa-outdoor-92-reversible-sectional-coffee-table-set-h5157_main.jpg',
    'LS_ID' => '800S',
    'viewers' => 34,
  ),
  20 => 
  array (
    'id' => 35954,
    'product_description' => 'Crafted in a Fair Trade Certified™ facility of solid mango wood, our Anton Bed
is a modern take on the rustic farmhouse look. Sturdy functionality meets
timeless elegance in this classically constructed bedroom staple.',
    'product_status' => 'active',
    'product_name' => 'Anton Solid Wood Bed',
    'product_sku' => 'anton-bed-h4426',
    'brand_name' => 'West Elm',
    'price' => '1699',
    'was_price' => '1699',
    'image' => '/westelm/westelm_images/anton-bed-h4426_main.jpg',
    'LS_ID' => '340',
    'viewers' => 31,
  ),
  21 => 
  array (
    'id' => 35326,
    'product_description' => 'Top this. Our Reeve Mid-Century Coffee Table\'s tailored lines and compact
footprint make it a great small-space solution. The marble top, solid wood
legs and brass brackets weather well the more you use it.

  * 30"diam. x 18.5"h.
  * Top made of carrara marble veneer over engineered wood.
  * Natural variations in the color and veining of marble make each piece subtly unique.
  * Solid wood legs with Pecan finish.
  * Brass-finished ferrules.
  * Imported.
  * Online/catalog only.',
    'product_status' => 'active',
    'product_name' => 'Reeve Mid-Century Coffee Table - Marble',
    'product_sku' => 'reeve-mid-century-coffee-table-marble-walnut-h520',
    'brand_name' => 'West Elm',
    'price' => '499',
    'was_price' => '499',
    'image' => '/westelm/westelm_images/reeve-mid-century-coffee-table-marble-walnut-h520_main.jpg',
    'LS_ID' => '225',
    'viewers' => 31,
  ),
  22 => 
  array (
    'id' => 35970,
    'product_description' => 'Our Penelope 5-Drawer Dresser\'s slim, tapered legs and refined profile are
inspired by mid-century design. Its marble top looks great styled with a table
lamp or a jewelry tree, while five rounded drawers provide plenty of storage.

###### KEY DETAILS

  * 26.2"w x 18"d x 52.25"h.
  * Marble top.
  * Engineered wood with ash veneer in a Feather Gray finish.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Five drawers open on wood glides.
  * Made in a [Fair Trade Certified™](https://www.westelm.com/pages/about-west-elm/our-commitments/fair-trade/) facility, directly benefiting the workers who make it.
  * Anti-tip kit hardware (included) is highly recommended to provide protection against tipping of furniture.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Penelope 5-Drawer Dresser - Feather Gray w/ Marble Top',
    'product_sku' => 'penelope-5-drawer-dresser-feather-gray-w-marble-top-h4970',
    'brand_name' => 'West Elm',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/westelm/westelm_images/penelope-5-drawer-dresser-feather-gray-w-marble-top-h4970_main.jpg',
    'LS_ID' => '334',
    'viewers' => 31,
  ),
  23 => 
  array (
    'id' => 35694,
    'product_description' => 'Our Mid-Century Expandable Dining Table adds two extra spaces when extended,
making it perfect for family meals and dinner parties alike. Its sturdy frame
is made from sustainably-sourced wood in a Fair Trade Certified™ facility.',
    'product_status' => 'active',
    'product_name' => 'Mid-Century Expandable Dining Table - Walnut',
    'product_sku' => 'parker-expandable-dining-table-g830',
    'brand_name' => 'West Elm',
    'price' => '699-899',
    'was_price' => '699-899',
    'image' => '/westelm/westelm_images/parker-expandable-dining-table-g830_main.jpg',
    'LS_ID' => '552',
    'viewers' => 28,
  ),
  24 => 
  array (
    'id' => 34797,
    'product_description' => 'Inspired by American modern design, the Mid-Century Wide Bookshelf borrows its
slim legs and beveled edges from iconic \'50s and \'60s furniture silhouettes.
Each piece is GREENGUARD-certified and made from sustainably-sourced wood in a
Fair Trade Certified™ facility.

  * 38"w x 15"d x 70.25"h.
  * Kiln-dried solid poplar wood and engineered wood.
  * All wood is sustainably-sourced and [FSC-certified](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/).
  * Covered in White paint on all sides.
  * Metal hardware in Antiqued Bronze finish.
  * Three fixed shelves.
  * Bottom drawer opens on smooth metal glides.
  * Legs include built-in levelers.
  * GREENGUARD [Gold Certified.](http://greenguard.org/en/CertificationPrograms/CertificationPrograms_childrenSchools.aspx) Meets or exceeds stringent chemical and VOC emissions standards.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * Made in a [Fair Trade Certified™](https://www.westelm.com/pages/about-west-elm/our-commitments/fair-trade/) facility in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Mid-Century 38" Bookshelf - White',
    'product_sku' => 'mid-century-38-bookshelf-white-h3278',
    'brand_name' => 'West Elm',
    'price' => '699',
    'was_price' => '699',
    'image' => '/westelm/westelm_images/mid-century-38-bookshelf-white-h3278_main.jpg',
    'LS_ID' => '632',
    'viewers' => 28,
  ),
  25 => 
  array (
    'id' => 37978,
    'product_description' => 'now you see them. Now you don\'t. These transparent surfaces float in the room without taking up permanent visual residency. Thick molded acrylic adds clean mod edge in one seamless turn. Clean plexi look works easy as 1-2-3 for living room entertaining or bedside stacks. Nest together or scatter room to room.',
    'product_status' => 'active',
    'product_name' => '3-Piece Peekaboo Acrylic Nesting Table Set',
    'product_sku' => '102926',
    'brand_name' => 'CB2',
    'price' => '249',
    'was_price' => '199',
    'image' => '/cb2/_images/main/3-piece-peekaboo-acrylic-nesting-table-set.jpg',
    'LS_ID' => '225,335,99',
    'viewers' => 27,
  ),
  26 => 
  array (
    'id' => 37078,
    'product_description' => 'Two generously scaled pieces add up to one cozy sheltering spot for large rooms and large crowds. Tailored in snow white performance fabric, chaise and sofa sit deep and roomy for curling up or stretching out with the perfect balance of slouch and support. Cushions will continue to relax over time for a casual, comfy look, all elevated on tapered legs that add a subtle gleam in brushed aluminum. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Decker 2-Piece Snow Sectional Sofa',
    'product_sku' => '340239',
    'brand_name' => 'CB2',
    'price' => '3298',
    'was_price' => '2898',
    'image' => '/cb2/_images/main/decker-2-piece-snow-sectional-sofa.jpg',
    'LS_ID' => '202',
    'viewers' => 27,
  ),
  27 => 
  array (
    'id' => 34806,
    'product_description' => 'We gave our Portside Dining Set a rustic look by wire brushing its solid wood
frame and adding a weathered finish.

###### KEY DETAILS

  * Set includes one dining table and two benches.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Moisture-resistant solid mahogany and solid eucalyptus wood with wire-brushed surface.
  * Table features post legs.
  * Outdoor cover available (sold separately).
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Portside Outdoor 58.5" Dining Table & 47" Bench Set',
    'product_sku' => 'portside-dining-sets-h1871',
    'brand_name' => 'West Elm',
    'price' => '599',
    'was_price' => '599',
    'image' => '/westelm/westelm_images/portside-dining-sets-h1871_main.jpg',
    'LS_ID' => '800S',
    'viewers' => 26,
  ),
  28 => 
  array (
    'id' => 35545,
    'product_description' => 'A sophisticated approach to storage, our Foundry Collection lays a stylish
foundation with its streamlined frame and FSC®-certified wood. This media
console is equipped with two drawers, a roomy cabinet and open compartments to
display (or hide away) books, electronics and more.

###### KEY DETAILS

  * 69"w x 15.75"d x 24"h.
  * Kiln-dried solid mahogany & engineered wood with a Walnut veneer.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Covered in a water-based Dark Walnut finish.
  * Metal legs & accents in an Antique Bronze finish.
  * Accommodates a flat screen TV up to 50"w.
  * Two doors open to reveal interior storage space.
  * Two drawers open on metal glides.
  * Legs feature adjustable levelers to adapt to varying floor levels.
  * Anti-tip kit hardware (included) is highly recommended to provide protection against tipping of furniture.
  * This contract grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Foundry Media Console (69") - Dark Walnut',
    'product_sku' => 'foundry-media-console-69-dark-walnut-h4322',
    'brand_name' => 'West Elm',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/westelm/westelm_images/foundry-media-console-69-dark-walnut-h4322_main.jpg',
    'LS_ID' => '430',
    'viewers' => 25,
  ),
  29 => 
  array (
    'id' => 37096,
    'product_description' => 'Seize the day. The perfect setting for daily siestas. Designed by Mermelada Estudio in Spain, graceful daybed approaches sofa styling with the relaxed comfort of a bed. Subtly whitewashed, solid rubberwood frames airy panels handwoven of natural rattan. Inspired by the Mediterranean ritual of taking a relaxing moment after lunch, the designers reflect, "The straightforward wood structure combines with the lightness of the cane weaving for a fresh, contemporary look. The daybed is really complete when you fill it with a mix and match of pillows." Includes fibercore daybed mattress/trundle with a warm pearl white cotton blend mattress cover. CB2 exclusive. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Boho Natural Daybed with Pearl White Mattress Cover',
    'product_sku' => '529300',
    'brand_name' => 'CB2',
    'price' => '1249',
    'was_price' => '1249',
    'image' => '/cb2/_images/main/boho-natural-daybed-with-pearl-white-mattress-cover.jpg',
    'LS_ID' => '342,206',
    'viewers' => 23,
  ),
  30 => 
  array (
    'id' => 34742,
    'product_description' => 'Our GREENGUARD-certified Mid-Century Collection combines timeless style with
durable craftsmanship. Each piece is made in a Fair Trade Certified™ facility
from sustainably-sourced wood that\'s kiln-dried for extra strength, complete
with child-safe, water-based finishes. These Twin and Full beds are the
natural upgrade once your child outgrows his or her toddler bed.

**KEY DETAILS**

  * Kiln-dried solid eucalyptus wood and engineered wood with an Acacia wood veneer.
  * All wood is sustainably-sourced and [FSC®-certified](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/).
  * Covered in child-safe, water-based Acorn finish.
  * Accommodates most standard mattresses; box spring optional.
  * [GREENGUARD Gold Certified.](http://greenguard.org/en/CertificationPrograms/CertificationPrograms_childrenSchools.aspx) Meets or exceeds stringent chemical and VOC emissions standards.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * Made in a [Fair Trade Certified™](https://www.westelm.com/pages/about-west-elm/our-commitments/fair-trade/) facility in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Mid-Century Kids\' Bed - Acorn',
    'product_sku' => 'mid-century-kids-bed-acorn-h3231',
    'brand_name' => 'West Elm',
    'price' => '899-999',
    'was_price' => '899-999',
    'image' => '/westelm/westelm_images/mid-century-kids-bed-acorn-h3231_main.jpg',
    'LS_ID' => '941',
    'viewers' => 22,
  ),
  31 => 
  array (
    'id' => 50750,
    'product_description' => 'Clever and compact, our exclusive secretary desk is a brilliant solution for small home offices and workspaces. Like a vintage desk from the early 20th century, the top lifts to reveal shelf space, two drawers and a pullout tray for an extra work surface that doubles perfectly as a keyboard tray. Simply close the lid for tidy cleanup when you\'re done. An incredible value, this smart option is ultra-functional and exceptionally stylish.',
    'product_status' => 'active',
    'product_name' => 'Distressed Brown and Black Wood Secretary Desk',
    'product_sku' => '502391',
    'brand_name' => 'World Market',
    'price' => '249.99',
    'was_price' => '249.99',
    'image' => '/nw/images/46383_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '660',
    'viewers' => 21,
  ),
  32 => 
  array (
    'id' => 37003,
    'product_description' => 'a leg up. Plantation grown solid mango wood planks shift perspective in asymmetric wide/narrow leg rotation. Bird\'s eye view from the top is stunning: sweeping woodgrain in midtone brown, inset legs create directional geometry (and dinner conversation). Seats six. Small space scale (small price, too).',
    'product_status' => 'active',
    'product_name' => 'Blox 35x63 Dining Table',
    'product_sku' => '123863',
    'brand_name' => 'CB2',
    'price' => '499',
    'was_price' => '499',
    'image' => '/cb2/_images/main/blox-35x63-dining-table.jpg',
    'LS_ID' => '660',
    'viewers' => 20,
  ),
  33 => 
  array (
    'id' => 57852,
    'product_description' => 'The Harris was designed with one thing in mind: versatility. Neat, tailored
details and a slim, streamlined frame come together to create seating that
easily integrates into any style, whether modern, traditional or somewhere in
between. This lofted version comes with shapely wood legs and is hand
assembled and upholstered in the USA in your choice of fabric.

###### KEY DETAILS

  * Hand-built frames with hand-finished upholstery.
  * Engineered hardwood frame with mortise & tenon joinery.
  * All wood is kiln-dried for added durability.
  * Wood legs in a Pecan finish.
  * High-gauge sinuous springs provide cushion support.
  * Seat cushion has double wrapped, high-resiliency polyurethane foam core.
  * Back cushions are poly fiber filled.
  * Cushions are reversible (Astor Velvet excluded).
  * Legs feature adjustable levelers to adapt to varying floor levels.
  * Legs can be removed.
  * Assembled in the USA.',
    'product_status' => 'active',
    'product_name' => 'Harris Loft Sofa - Wood Legs',
    'product_sku' => 'harris-loft-sofa-wood-legs-h6390',
    'brand_name' => 'West Elm',
    'price' => '959.2-2798',
    'was_price' => '1099-2798',
    'image' => '/westelm/westelm_images/harris-loft-sofa-wood-legs-h6390_main.jpg',
    'LS_ID' => '201',
    'viewers' => 20,
  ),
  34 => 
  array (
    'id' => 57856,
    'product_description' => 'Crafted from durable solid mango wood that beautifully highlights the natural
grain of the wood, our Anton Entryway Bench is an updated take on the
versatile farmhouse style. Use it to sleekly add extra storage in tight
spaces, with a deep seat that\'s handy for taking off shoes.

###### KEY DETAILS

  * 44"w x 16"d x 17.5"h.
  * Solid mango wood frame and legs with a Burnt Wax finish.
  * The mango wood used on this product is sustainably sourced from trees that no longer produce fruit.
  * Natural variations in wood color and grain make each piece one of a kind.
  * Two doors open to reveal interior storage.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](http://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Anton Solid Wood Entryway Bench',
    'product_sku' => 'anton-solid-wood-entryway-bench-h5897',
    'brand_name' => 'West Elm',
    'price' => '699',
    'was_price' => '699',
    'image' => '/westelm/westelm_images/anton-solid-wood-entryway-bench-h5897_main.jpg',
    'LS_ID' => '216',
    'viewers' => 19,
  ),
  35 => 
  array (
    'id' => 35802,
    'product_description' => 'Bold in a burnished black finish, our Alexa Buffet is made from solid acacia
wood complete with a lightly distressed look. With plenty of shelf and cabinet
storage space for all of your dining and entertaining essentials, it combines
hardworking functionality with modern rusticity.

  * 88"w x 17"d x 33"h.
  * Solid acacia wood.
  * Burnished finish.
  * Antique Brass-finished hardware.
  * Six drawers open on smooth wood glides.
  * Made in Vietnam.
  * Online/catalog only.',
    'product_status' => 'active',
    'product_name' => 'Alexa Burnished Metal Buffet',
    'product_sku' => 'alexa-burnished-metal-media-buffet-h2531',
    'brand_name' => 'West Elm',
    'price' => '2099',
    'was_price' => '2099',
    'image' => '/westelm/westelm_images/alexa-burnished-metal-media-buffet-h2531_main.jpg',
    'LS_ID' => '534',
    'viewers' => 19,
  ),
  36 => 
  array (
    'id' => 51884,
    'product_description' => ' now you see it, now you don\'t.  This transparent "C" rolls in on four clear acrylic casters. Thick molded acrylic adds clean mod edge in one seamless turn.',
    'product_status' => 'active',
    'product_name' => 'Peekaboo Acrylic C Table',
    'product_sku' => '102871',
    'brand_name' => 'CB2',
    'price' => '199',
    'was_price' => '199',
    'image' => '/cb2/_images/main/peekaboo-acrylic-c-table.jpg',
    'LS_ID' => '226,99',
    'viewers' => 18,
  ),
  37 => 
  array (
    'id' => 51249,
    'product_description' => '',
    'product_status' => 'active',
    'product_name' => 'Remi Ottoman',
    'product_sku' => 'remi-ottoman-h6212',
    'brand_name' => 'West Elm',
    'price' => '499-649',
    'was_price' => '499-649',
    'image' => '/westelm/westelm_images/remi-ottoman-h6212_main.jpg',
    'LS_ID' => '221',
    'viewers' => 18,
  ),
  38 => 
  array (
    'id' => 35106,
    'product_description' => 'Characterized by its rounded frame, pillow-like back cushion and low-slung
seat, the Hanna Sofa has a look that\'s relaxed but still clean-cut. Its loose,
reversible cushions help it remain plush and looking new.

###### KEY DETAILS

  * 71.5"w x 34.75"d x 34"h.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](http://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Solid pine and engineered wood frame with reinforced joinery.
  * Solid oak legs in a water-based Almond finish.
  * All wood is kiln dried for extra durability.
  * High-gauge sinuous spring seat support; webbed back support.
  * Fiber-wrapped, high-resiliency polyurethane foam cushioning.
  * Seat firmness: Medium. On a scale from 1 to 5 (5 being firmest), we rate it a 3.
  * Loose, reversible cushions.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Hanna Sofa',
    'product_sku' => 'hanna-sofa-715-h3993',
    'brand_name' => 'West Elm',
    'price' => '1099-1399',
    'was_price' => '1099-1399',
    'image' => '/westelm/westelm_images/hanna-sofa-715-h3993_main.jpg',
    'LS_ID' => '201',
    'viewers' => 18,
  ),
  39 => 
  array (
    'id' => 35755,
    'product_description' => 'Made of solid wood, re-sawing and rough-hewing give our Logan Collection its
rugged good looks. The process makes each piece subtly one-of-a-kind.

###### KEY DETAILS

  * 24"w x 16"d x 23"h.
  * Solid acacia wood in a Natural finish.
  * Iron frame.
  * Drawer opens on smooth metal glides.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Logan Industrial Nightstand - Natural',
    'product_sku' => 'logan-nightstand-natural-h2023',
    'brand_name' => 'West Elm',
    'price' => '449-898',
    'was_price' => '449-898',
    'image' => '/westelm/westelm_images/logan-nightstand-natural-h2023_main.jpg',
    'LS_ID' => '335',
    'viewers' => 18,
  ),
  40 => 
  array (
    'id' => 51248,
    'product_description' => 'Our Remi Collection\'s low-slung style instantly makes for a brighter, airier
space. Compact and comfortable with a boxy, structured look, its high density
foam cushioning and spacious seats are meant for laidback lounging. It\'s
available in multiple modular configurations too, like this 2-Piece Armless
Sofa.

###### KEY DETAILS

  * 70"w x 35"d x 26.25"h.
  * Solid pine and engineered wood frame with reinforced joinery.
  * All wood is kiln dried for added durability.
  * Fully upholstered.
  * Plastic shadowline support legs in Black.
  * Frame padding made with polyurethane foam.
  * High-gauge sinuous springs provide cushion support.
  * Seat cushions have fiber-wrapped, high-resiliency polyurethane foam cores.
  * Seat firmness: Soft. On a scale from 1 to 5 (5 being firmest), we rate it a 2.
  * Back cushions have foam fill.
  * Collection is modular and can be arranged in your desired configuration.
  * Assembled in the USA.',
    'product_status' => 'active',
    'product_name' => 'Remi 2-Piece Armless Sofa',
    'product_sku' => 'remi-2-piece-armless-sofa-h6238',
    'brand_name' => 'West Elm',
    'price' => '998-1798',
    'was_price' => '998-1798',
    'image' => '/westelm/westelm_images/remi-2-piece-armless-sofa-h6238_main.jpg',
    'LS_ID' => '201',
    'viewers' => 18,
  ),
  41 => 
  array (
    'id' => 51294,
    'product_description' => 'Our Grid-Tufted Wide Bed gets a practical update: storage drawers open on each
side, revealing ample space to store clothes and bedding. The plush, extra-
wide headboard gets its shape from geometric detailing rather than traditional
buttons, for a tufted look that\'s comfortable to lean on at any angle.

###### KEY DETAILS

  * Choose your headboard height: Low (38"), Standard (46") or Tall (54").
  * Padded upholstery over solid pine & engineered wood.
  * Solid wood legs in a Walnut finish.
  * All wood is kiln-dried for added durability.
  * Sizable pull-out storage drawers (2 on each side).
  * Drawers open on metal glides.
  * Engineered wood slats included.
  * Accommodates most standard mattresses; box spring optional.
  * Select styles include extra mattress space so that bedding can be tucked under. Please see Details + Dimensions tab for mattress space dimensions.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Made in China.',
    'product_status' => 'active',
    'product_name' => 'Grid-Tufted Upholstered Wide Storage Bed',
    'product_sku' => 'grid-tufted-upholstered-wide-storage-bed-h5966',
    'brand_name' => 'West Elm',
    'price' => '2099-2799',
    'was_price' => '2099-2799',
    'image' => '/westelm/westelm_images/grid-tufted-upholstered-wide-storage-bed-h5966_main.jpg',
    'LS_ID' => '344',
    'viewers' => 18,
  ),
  42 => 
  array (
    'id' => 49027,
    'product_description' => 'Our iconic Papasan Chair is handcrafted of natural—and naturally durable—rattan, then finished with a rich, brown stain and high-gloss lacquer. All of which is just another way of saying, "Ahhhhhhhh."<span class="mini-upsell" data-launch="false" data-verbose="true" data-productName="Papasan Stool" data-skus="PV895-06:1;PV210-5:1"></span>',
    'product_status' => 'active',
    'product_name' => 'Taupe Chair Base',
    'product_sku' => '2440344',
    'brand_name' => 'Pier1',
    'price' => '45',
    'was_price' => '45',
    'image' => '/Pier-1/pier1_images/2440344_main.jpg',
    'LS_ID' => '217',
    'viewers' => 17,
  ),
  43 => 
  array (
    'id' => 35916,
    'product_description' => 'See and be seen. The Archway Windowed Cabinet\'s classical shape and formal
design makes a statement while housing barware, books, or any collection of
treasures you want to put on display. The iron frame and brass hardware add a
chic touch to any room.

  * 39.5"w x 16.5"d x 92.5"h.
  * Four interior compartments with fixed shelves.
  * Iron frame in Matte Black.
  * Hardware in Brass finish.
  * Clear glass windows.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Archway Windowed Cabinet',
    'product_sku' => 'archway-windowed-cabinet-h4453',
    'brand_name' => 'West Elm',
    'price' => '1999',
    'was_price' => '1999',
    'image' => '/westelm/westelm_images/archway-windowed-cabinet-h4453_main.jpg',
    'LS_ID' => '430',
    'viewers' => 17,
  ),
  44 => 
  array (
    'id' => 25544,
    'product_description' => 'Picking up on the best of mid-century design, Tate streamlines a striking slatted bench with smart conical legs in beautiful solid walnut. The perfect partner to our Tate bedroom collection, the bench brings its classic modern style to any room in the house. Top with our optional  button-tufted cushion. Designed by Blake Tovin, the Tate Walnut Slatted Bench is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Tate Walnut Slatted Bench',
    'product_sku' => '216711',
    'brand_name' => 'Crate&Barrel',
    'price' => '549',
    'was_price' => '549',
    'image' => '/cnb/images/main/tate-walnut-slatted-bench.jpg',
    'LS_ID' => '216,223,310,410',
    'viewers' => 16,
  ),
  45 => 
  array (
    'id' => 34836,
    'product_description' => 'With its modern form and crisp tailoring, our spacious Andes Sofa has serious
presence but feels airy and light thanks to thin arms and cast metal legs. Its
durable frame is Contract Grade and comes in your choice of width, depth and
leg finish.',
    'product_status' => 'active',
    'product_name' => 'Andes Sofa',
    'product_sku' => 'andes-sofa-h1844',
    'brand_name' => 'West Elm',
    'price' => '1039.2-1899',
    'was_price' => '1299-1899',
    'image' => '/westelm/westelm_images/andes-sofa-h1844_main.jpg',
    'LS_ID' => '201',
    'viewers' => 16,
  ),
  46 => 
  array (
    'id' => 51287,
    'product_description' => 'Our light and airy Scout Wide Bookshelf blends easily into any room, but don\'t
underestimate its five roomy, rounded shelves. Solidly crafted of wood with
metal supports and double the amount of space, they hold a surprising amount
of books, dinnerware, office supplies or accessories with their durable,
contract-grade construction.

###### KEY DETAILS

  * 56.4"w x 15.4"d x 72.25"h.
  * Kiln-dried engineered wood shelves in White.
  * Spun metal frame in White.
  * 5 fixed shelves.
  * Legs feature adjustable levelers to adapt to varying floor levels.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Scout Wide Bookshelf',
    'product_sku' => 'scout-wide-bookshelf-h6056',
    'brand_name' => 'West Elm',
    'price' => '699',
    'was_price' => '699',
    'image' => '/westelm/westelm_images/scout-wide-bookshelf-h6056_main.jpg',
    'LS_ID' => '231',
    'viewers' => 16,
  ),
  47 => 
  array (
    'id' => 35448,
    'product_description' => 'Available in a range of finishes so you can choose the one that\'s just right
for your space, our Streamline Console\'s slim profile is sized just right for
narrow hallways or smaller living rooms. This glass-topped version lends your
space an airy touch.

  * 36"w x 10"d x 30.3"h.
  * Tempered glass top.
  * Metal base.
  * Top is made in China; base is made in India.',
    'product_status' => 'active',
    'product_name' => 'Streamline Console - Glass',
    'product_sku' => 'streamline-console-glass-h3748',
    'brand_name' => 'West Elm',
    'price' => '299',
    'was_price' => '299',
    'image' => '/westelm/westelm_images/streamline-console-glass-h3748_main.jpg',
    'LS_ID' => '227',
    'viewers' => 16,
  ),
  48 => 
  array (
    'id' => 34719,
    'product_description' => 'We gave our Portside Corner Sofa its rustic look by wire brushing the solid
wood frame and adding a weathered finish. We included yarn-dyed cushions for
the ultimate outdoor lounging spot.

###### KEY DETAILS

  * 112.4"w x 37.3"d x 30"h. 
  * Moisture-resistant solid mahogany and solid eucalyptus wood with wire-brushed surface.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Yarn-dyed weather-resistant cushions in Gray (included).
  * Outdoor cover available (sold separately).
  * Frame made in Indonesia; cushions made in China.',
    'product_status' => 'active',
    'product_name' => 'Portside Outdoor Low 2-Piece Corner Sofa',
    'product_sku' => 'portside-outdoor-low-2-piece-corner-sofa-h5187',
    'brand_name' => 'West Elm',
    'price' => '2098',
    'was_price' => '2098',
    'image' => '/westelm/westelm_images/portside-outdoor-low-2-piece-corner-sofa-h5187_main.jpg',
    'LS_ID' => '806',
    'viewers' => 16,
  ),
  49 => 
  array (
    'id' => 51268,
    'product_description' => 'Low-slung and loungy, our Tillary® Sectional features weighted back cushions
that allow you to arrange the sofa multiple ways. Each piece is crafted in the
USA with hand-built frames and cushions and hand-finished upholstery.

###### KEY DETAILS

  * 112.5"w x 112.5"d x 27"h.
  * Solid oak and engineered hardwood frame with reinforced joinery.
  * Solid wood legs with Chocolate-stained finish.
  * All wood is kiln dried for added durability.
  * High-gauge sinuous springs provide cushion support.
  * Seat cushions have fiber-wrapped, high-resiliency polyurethane foam cores.
  * Seat firmness: Firm. On a scale from 1 to 5 (5 being firmest), we rate it a 5.
  * Weighted back cushions are fiber filled.
  * Remove or rearrange back cushions to create extra lounging surface.
  * Assembled in the USA.',
    'product_status' => 'active',
    'product_name' => 'Tillary® 8-Piece Sectional',
    'product_sku' => 'tillary-8-piece-sectional-feather-gray-chenille-tweed-h2982',
    'brand_name' => 'West Elm',
    'price' => '3292-4642',
    'was_price' => '3292-4642',
    'image' => '/westelm/westelm_images/tillary-8-piece-sectional-feather-gray-chenille-tweed-h2982_main.jpg',
    'LS_ID' => '202',
    'viewers' => 16,
  ),
  50 => 
  array (
    'id' => 26806,
    'product_description' => 'There\'s a reason it\'s called Lounge. This extra-long sofa, part of our ultimate family room collection, features super-deep, low seats and super-soft back cushions that invite family to pile on and sink in. ',
    'product_status' => 'active',
    'product_name' => 'Lounge II 93" Sofa',
    'product_sku' => '302835',
    'brand_name' => 'Crate&Barrel',
    'price' => '1799',
    'was_price' => '1799',
    'image' => '/cnb/images/main/lounge-ii-93-sofa.jpg',
    'LS_ID' => '201,99',
    'viewers' => 15,
  ),
  51 => 
  array (
    'id' => 36122,
    'product_description' => 'Our Oak Wood-Wrapped 6-Drawer Dresser is a mid-century inspired design with a
modern, industrial touch. This sophisticated piece is an easy fit with just
about any style room.

###### KEY DETAILS

  * 65"w x 18"d x 33"h.
  * Iron frame in a Black finish.
  * Oak veneer drawer fronts in a Warm Oak finish.
  * Metal accents in an Antique Brass finish.
  * 6 drawers open on metal glides.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Oak Wood-Wrapped 6-Drawer Dresser',
    'product_sku' => 'metal-wrapped-oak-wood-6-drawer-dresser-h4090',
    'brand_name' => 'West Elm',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/westelm/westelm_images/metal-wrapped-oak-wood-6-drawer-dresser-h4090_main.jpg',
    'LS_ID' => '334',
    'viewers' => 15,
  ),
  52 => 
  array (
    'id' => 97115,
    'product_description' => 'Combining Fully’s obsessively engineered steel Jarvis Frame, and Floyd’s real birch ply table surface and all-natural linoleum, The Standing desk is built to withstand the longest hours, and',
    'product_status' => 'active',
    'product_name' => 'The Floyd Standing Desk',
    'product_sku' => 'the-standing-desk',
    'brand_name' => 'Floyd',
    'price' => '740-775',
    'was_price' => '740-895',
    'image' => '/floyd/images/StandingDesk/05-standing-desk-carousel.jpg',
    'LS_ID' => '660',
    'viewers' => 14,
  ),
  53 => 
  array (
    'id' => 57851,
    'product_description' => 'The Harris was designed with one thing in mind: versatility. Neat, tailored
details and a slim, streamlined frame come together to create seating that
easily integrates into any style, whether modern, traditional or somewhere in
between. This lofted version comes with shapely wood legs and is assembled in
the USA in your choice of fabric.

###### KEY DETAILS

  * 106"w x 62"d x 34"h.
  * Hand-built frames with hand-finished upholstery.
  * Engineered hardwood frame with mortise & tenon joinery.
  * All wood is kiln-dried for added durability.
  * Wood legs in a Pecan finish.
  * High-gauge sinuous springs provide cushion support.
  * Seat cushions have double wrapped, high-resiliency polyurethane foam core.
  * Back cushions are poly fiber filled.
  * Loose cushions.
  * Legs feature adjustable levelers to adapt to varying floor levels.
  * Assembled in the USA.',
    'product_status' => 'active',
    'product_name' => 'Harris Loft 2-Piece Chaise Sectional - Wood Legs',
    'product_sku' => 'harris-loft-2-piece-chaise-sectional-wood-legs-h6391',
    'brand_name' => 'West Elm',
    'price' => '559.2-3997',
    'was_price' => '699-3997',
    'image' => '/westelm/westelm_images/harris-loft-2-piece-chaise-sectional-wood-legs-h6391_main.jpg',
    'LS_ID' => '202',
    'viewers' => 14,
  ),
  54 => 
  array (
    'id' => 35316,
    'product_description' => 'Part sculpture, part table, all artisanal. Craftspeople in Jaipur, India, hand
carved the delicate rosettes on this low-sitting solid mango wood table, which
takes its original inspiration from a ceremonial stool used by Bamileke
royalty in the African country of Cameroon.  

  * 32"diam. x 12"h.
  * Solid mango wood.
  * Hand-carved rosette pattern; each piece is unique.  

  * Natural variations in the wood grain and coloring are to be expected.
  * To prevent cracking, protect solid wood from significant temperature and humidity fluctuations.  

  * The mango wood used on this product is sustainably sourced from trees that no longer produce fruit. 
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Carved Wood Coffee Table',
    'product_sku' => 'carved-wood-coffee-table-g414',
    'brand_name' => 'West Elm',
    'price' => '399',
    'was_price' => '399',
    'image' => '/westelm/westelm_images/carved-wood-coffee-table-g414_main.jpg',
    'LS_ID' => '225',
    'viewers' => 13,
  ),
  55 => 
  array (
    'id' => 51292,
    'product_description' => 'An extra-wide, channel-tufted headboard adds a touch of drama to our Andes
Bed, creating an elegant backdrop that grounds your room. Shapely metal legs
lighten the look and a range of fabric options lets you pick the right look
for you. Even better: Its Contract Grade frame means it\'s extra sturdy.

###### KEY DETAILS

  * Choose your headboard height: Low (38"), Standard (46") or Tall (58").
  * Thickly padded and tightly upholstered in your choice of printed upholstery fabric.
  * Solid and engineered wood frame with reinforced joinery.
  * Solid beechwood support slats are included.
  * All wood is kiln dried for added durability.
  * Cast iron legs in your choice of finish.
  * Built-in levelers on legs provide stability on uneven surfaces.
  * Queen and King styles include 3 middle support legs.
  * Accommodates most standard mattresses; box spring optional.
  * Select styles include extra mattress space so that bedding can be tucked under. Please see Details + Dimensions tab for mattress space dimensions.
  * Compatible with most adjustable base mattresses; Please refer to the mattress space dimensions listed under the Details + Dimensions tab.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Designed in Brooklyn.
  * Made in China.',
    'product_status' => 'active',
    'product_name' => 'Andes Deco Upholstered Wide Channel Bed',
    'product_sku' => 'andes-deco-upholstered-wide-channel-bed-h5957',
    'brand_name' => 'West Elm',
    'price' => '1699-2399',
    'was_price' => '1699-2399',
    'image' => '/westelm/westelm_images/andes-deco-upholstered-wide-channel-bed-h5957_main.jpg',
    'LS_ID' => '340',
    'viewers' => 13,
  ),
  56 => 
  array (
    'id' => 25943,
    'product_description' => 'Stylish and contemporary Lowe wraps the classic Parsons-style chair in pure color, with a wide range of hues in pebbled, bicast leather. With a roomy cushioned seat and back, it\'s designed for lingering. ',
    'product_status' => 'active',
    'product_name' => 'Lowe Onyx Leather Dining Chair',
    'product_sku' => '305358',
    'brand_name' => 'Crate&Barrel',
    'price' => '219',
    'was_price' => '199',
    'image' => '/cnb/images/main/lowe-onyx-leather-side-chair.jpg',
    'LS_ID' => '510,99',
    'viewers' => 12,
  ),
  57 => 
  array (
    'id' => 35622,
    'product_description' => 'Made from richly-grained solid mango wood and supported by sturdy steel legs,
our Industrial Modular Bookcase includes compartments for displaying and
organizing books, magazines and other knickknacks. The natural variations in
mango wood mean that each piece is subtly unique.

  * 64"w x 16"d x 30"h.
  * Solid mango wood; metal frame with a Black finish.
  * The mango wood used on this product is sustainably sourced from trees that no longer produce fruit. 
  * Due to natural variations in the wood, each piece will be subtly unique.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Industrial Modular Bookcase',
    'product_sku' => 'rustic-storage-bookcase-h830',
    'brand_name' => 'West Elm',
    'price' => '899',
    'was_price' => '899',
    'image' => '/westelm/westelm_images/rustic-storage-bookcase-h830_main.jpg',
    'LS_ID' => '632',
    'viewers' => 12,
  ),
  58 => 
  array (
    'id' => 25922,
    'product_description' => 'Willow\'s more modern lines relax in cottage style, instantly putting family rooms and casual living rooms in a vacation state of mind. Deep cushions and a machine-washable slipcover are tailored in a cotton-blend fabric pre-washed for a softer, lived-in touch. ',
    'product_status' => 'active',
    'product_name' => 'Willow Modern Slipcovered Sofa',
    'product_sku' => '258002',
    'brand_name' => 'Crate&Barrel',
    'price' => '1899',
    'was_price' => '1899',
    'image' => '/cnb/images/main/willow-modern-slipcovered-queen-sleeper-sofa-with-air-mattress.jpg',
    'LS_ID' => '201,99',
    'viewers' => 11,
  ),
  59 => 
  array (
    'id' => 25671,
    'product_description' => 'Inspired by mid-century modern design, the Elke end table\'s sculptural cast base takes a refined architectural stance. Topped with a round top of white marble, the hand-cast aluminum base is has a wishbone-shaped design and warm, polished brass finish with a touch of antiquing. The Elke Round Marble End Table with Brass Base is a Crate and Barrel exclusive.Create an amazing reading nook using these great ideas.',
    'product_status' => 'active',
    'product_name' => 'Elke Round Marble End Table with Brass Base',
    'product_sku' => '337180',
    'brand_name' => 'Crate&Barrel',
    'price' => '599',
    'was_price' => '599',
    'image' => '/cnb/images/main/elke-round-marble-end-table-with-brass-base.jpg',
    'LS_ID' => '226',
    'viewers' => 11,
  ),
  60 => 
  array (
    'id' => 39721,
    'product_description' => 'Curl up with this fuzzy Papasan cushion and unwind. To find the most comfortable position, simply move around until everything feels just right because that\'s what this cushion was created for: Total relaxation.',
    'product_status' => 'active',
    'product_name' => 'Fuzzy Sand Papasan Chair Cushion',
    'product_sku' => '2461640',
    'brand_name' => 'Pier1',
    'price' => '90',
    'was_price' => '90',
    'image' => '/Pier-1/pier1_images/2461640_main.jpg',
    'LS_ID' => '217',
    'viewers' => 11,
  ),
  61 => 
  array (
    'id' => 50178,
    'product_description' => 'A natural finish highlights the solid acacia wood construction of our fixed dining table. This modern dining room centerpiece features a clean-lined silhouette detailed with inset legs that add dimension and architectural interest.',
    'product_status' => 'active',
    'product_name' => 'Natural Wood Finn Dining Table',
    'product_sku' => '57001576',
    'brand_name' => 'World Market',
    'price' => '699.99',
    'was_price' => '699.99',
    'image' => '/nw/images/81417_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '551',
    'viewers' => 11,
  ),
  62 => 
  array (
    'id' => 36093,
    'product_description' => 'Pairing a solid wood top with sturdy cast metal legs, this nightstand adds
modern industrial style beside any bed or sofa. Designed within its handsome
silhouette are two tabletop clips and a hollow leg trough to keep cords
managed and out of sight—simply clip the cord to the tabletop and guide it
through the leg trough to the wall outlet.

  * Contact [MarriottProcurementFourPoints@marriott.com](mailto:MarriottProcurementFourPoints@marriott.com) for program pricing and project management services for all Four Points Hotels.
  * 24"diam. x 26"h.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](http://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Mango wood top with a Café finish.
  * Cast metal legs with a Hot-Rolled Steel finish.
  * Two clips on back of tabletop for cord management.
  * Legs have hollow troughs that cords can be guided through from clip to wall outlet.
  * This piece\'s finish develops a patina over time from scratches and natural variations in tone.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Cast Base Table',
    'product_sku' => 'cast-base-nightstand-h2798',
    'brand_name' => 'West Elm',
    'price' => '179',
    'was_price' => '154.84',
    'image' => '/westelm/westelm_images/cast-base-nightstand-h2798_main.jpg',
    'LS_ID' => '335',
    'viewers' => 11,
  ),
  63 => 
  array (
    'id' => 99896,
    'product_description' => 'Crafted of solid mango wood that beautifully shows off the natural grain, our Anton Dining Table is an updated take on the versatile farmhouse table. This substantial table is perfect for everything from family dinners to game
nights.',
    'product_status' => 'active',
    'product_name' => 'Anton Solid Wood Dining Table - Burnt Wax',
    'product_sku' => 'anton-solid-wood-dining-table-h4231',
    'brand_name' => 'West Elm',
    'price' => '1099-1299',
    'was_price' => '1099-1299',
    'image' => '/westelm/westelm_images/anton-solid-wood-dining-table-h4231_main.jpg',
    'LS_ID' => '551',
    'viewers' => 11,
  ),
  64 => 
  array (
    'id' => 51253,
    'product_description' => 'Low-slung and boxy, our Remi Collection is deceptively plush thanks to a top
layer of memory foam. This 4-Piece Sectional fits three people comfortably,
with deep seats and a removable slip cover for easy cleaning.',
    'product_status' => 'active',
    'product_name' => 'Remi Slip Cover 4-Piece Sectional',
    'product_sku' => 'remi-slip-cover-4-piece-sectional-h6277',
    'brand_name' => 'West Elm',
    'price' => '2796-3996',
    'was_price' => '2796-3996',
    'image' => '/westelm/westelm_images/remi-slip-cover-4-piece-sectional-h6277_main.jpg',
    'LS_ID' => '202',
    'viewers' => 11,
  ),
  65 => 
  array (
    'id' => 34801,
    'product_description' => 'We made pairing easy with this ready-to-buy set. Our Portside Dining Table is
outfitted with a rustic look by wire brushing its solid wood frame and adding
a weathered finish. With four textilene-clad chairs included, it\'s the perfect
addition for outdoor dinner parties and barbecues.

###### KEY DETAILS

  * Bundle includes 1 Portside Outdoor 58.5" Dining Table and 4 Portside Outdoor Textiliene Dining Chairs.
  * Table: Moisture-resistant solid mahogany and solid eucalyptus wood with wire-brushed surface.
  * Chairs: Moisture-resistant solid mahogany and solid eucalyptus wood with wire-brushed surface and easy-to-clean textilene® fabric.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Outdoor covers available (sold separately).
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Portside Outdoor 58.5" Dining Table & Textilene Chairs Set',
    'product_sku' => 'portside-58-5-dining-table-4-textilene-chairs-set-h4480',
    'brand_name' => 'West Elm',
    'price' => '599',
    'was_price' => '599',
    'image' => '/westelm/westelm_images/portside-58-5-dining-table-4-textilene-chairs-set-h4480_main.jpg',
    'LS_ID' => '800S',
    'viewers' => 11,
  ),
  66 => 
  array (
    'id' => 50402,
    'product_description' => 'An inspired take on the traditional chesterfield sofa, the Quentin features classic strong lines, button-tufted upholstery and straight upholstered arms. Available in neutral gray-tweed upholstery or a smooth charcoal gray, our exclusive sofa\'s soft fabric gives it an approachable appeal that\'s echoed by sculpted hardwood legs in a natural finish. Comfy yet tailored, this statement sofa beckons guests to sit and stay awhile.',
    'product_status' => 'active',
    'product_name' => 'Quentin Chesterfield Sofa',
    'product_sku' => '541136',
    'brand_name' => 'World Market',
    'price' => '749.99',
    'was_price' => '749.99',
    'image' => '/nw/images/69445_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '201',
    'viewers' => 10,
  ),
  67 => 
  array (
    'id' => 96774,
    'product_description' => 'Meet Strato. Designed by Mermelada Estudio, this vintage \'70s-inspired sectional sofa is upholstered in soft white boucle with plush channel tufting on all sides. "Cozy" doesn\'t do it justice. Learn more about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Strato 4-Piece Boucle Sectional Sofa',
    'product_sku' => '604903',
    'brand_name' => 'CB2',
    'price' => '3996',
    'was_price' => '3097',
    'image' => '/cb2/_images/main/strato-4-piece-boucle-sectional-sofa.jpg',
    'LS_ID' => '202',
    'viewers' => 10,
  ),
  68 => 
  array (
    'id' => 51158,
    'product_description' => 'Convertible sleeper sofa gets a stylish update in luxe, ivory boucle upholstery. By day, it\'s an upright but comfy lounge that can also angle back 45 degrees for TV/reading. At night, flip the back all the way down and voilà: a worthy platform bed with real pocketed coil mattress innersprings that sleeps two. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Una Ivory Boucle Sleeper Sofa',
    'product_sku' => '657955',
    'brand_name' => 'CB2',
    'price' => '1699',
    'was_price' => '1699',
    'image' => '/cb2/_images/main/una-ivory-boucle-sleeper-sofa.jpg',
    'LS_ID' => '205,201',
    'viewers' => 10,
  ),
  69 => 
  array (
    'id' => 25944,
    'product_description' => 'Stylish and contemporary Lowe wraps the classic Parsons-style chair in pure color, with a wide range of hues in pebbled, bicast leather. With a roomy cushioned seat and back, it\'s designed for lingering. ',
    'product_status' => 'active',
    'product_name' => 'Lowe Persimmon Leather Dining Chair',
    'product_sku' => '305991',
    'brand_name' => 'Crate&Barrel',
    'price' => '219',
    'was_price' => '199',
    'image' => '/cnb/images/main/lowe-persimmon-leather-side-chair.jpg',
    'LS_ID' => '510,99',
    'viewers' => 9,
  ),
  70 => 
  array (
    'id' => 50221,
    'product_description' => 'Crafted of solid rubberwood and acacia wood, our petite chairs boast a low profile that makes them ideal for small dining areas. These traditional side chairs are finished in weathered gray with visible wood grain for a textural appeal.',
    'product_status' => 'active',
    'product_name' => 'Weathered Gray Wood Jozy Dining Chairs Set of 2',
    'product_sku' => '526102',
    'brand_name' => 'World Market',
    'price' => '139.98',
    'was_price' => '139.98',
    'image' => '/nw/images/60765_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '510',
    'viewers' => 9,
  ),
  71 => 
  array (
    'id' => 37002,
    'product_description' => 'shine and dine. A gilded update to our popular chrome version. Two sleek brass "sawhorses" partner with crisp, clear glass tops, rectangle or round—your choice. For more leg room and stability, turn the brass-plated iron structures T-in at the ends (shown). silverado brass 72" rectangular dining table is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Silverado Brass 72" Rectangular Dining Table',
    'product_sku' => '593741',
    'brand_name' => 'CB2',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cb2/_images/main/silverado-brass-72-rectangular-dining-table.jpg',
    'LS_ID' => '660',
    'viewers' => 9,
  ),
  72 => 
  array (
    'id' => 53702,
    'product_description' => 'Put your feet up! Our small-scaled, round Isla Ottoman in your choice of
upholstery will turn any armchair into a recliner, and it works well as a side
table or extra seating, too. Pair it with an armchair or use on its own for
versatile options in a small space.

###### KEY DETAILS

  * 16.5"diam. x 19"h.
  * Kiln-dried pine and engineered wood frame.
  * Webbed cushion support.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Made in China.',
    'product_status' => 'active',
    'product_name' => 'Isla Ottoman - Small',
    'product_sku' => 'isla-ottoman-small-h6264',
    'brand_name' => 'West Elm',
    'price' => '199-349',
    'was_price' => '199-349',
    'image' => '/westelm/westelm_images/isla-ottoman-small-h6264_main.jpg',
    'LS_ID' => '221',
    'viewers' => 9,
  ),
  73 => 
  array (
    'id' => 49234,
    'product_description' => 'Our low Denise Bookshelf\'s minimalist metal frame and glass shelves really let
your books, decor and picture frames take center stage. Four shelves at varied
heights ensure there\'s a spot for everything, big and small.

###### KEY DETAILS

  * 60"w x 13"d x 24"h.
  * Metal frame.
  * Clear tempered glass shelves with mirrored bottom shelf.
  * Four fixed shelves.
  * Anti-tip kit hardware (included) is highly recommended to provide protection against tipping of furniture.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](http://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Denise Low Bookshelf',
    'product_sku' => 'denise-low-bookshelf-h5902',
    'brand_name' => 'West Elm',
    'price' => '418.99',
    'was_price' => '699',
    'image' => '/westelm/westelm_images/denise-low-bookshelf-h5902_main.jpg',
    'LS_ID' => '231',
    'viewers' => 9,
  ),
  74 => 
  array (
    'id' => 28038,
    'product_description' => 'We\'ve enlisted highly skilled Javanese artisans to create this statement-making cabinet.  Using the traditional carving tools of chisel and mallet, the woodworkers rely on skills passed down from generation to generation to grace the cabinet\'s natural teak doors with a constellation of timeless shapes and symbols.  ',
    'product_status' => 'active',
    'product_name' => 'Ancestors Hand Carved Teak Cabinet',
    'product_sku' => '147131',
    'brand_name' => 'Crate&Barrel',
    'price' => '2999',
    'was_price' => '2999',
    'image' => '/cnb/images/main/ancestors-hand-carved-teak-cabinet.jpg',
    'LS_ID' => '232,99',
    'viewers' => 8,
  ),
  75 => 
  array (
    'id' => 26209,
    'product_description' => 'Convex facets create a delightful play of light and shadow on our geo-forward garden stool. Glazed deep mauven, the ceramic stool also tucks next to an outdoor sofa or lounge chair as a sculptural end table. Handmade exclusively for Crate and Barrel, each unique garden stool/end table will vary slightly in color, shape and dimensions.',
    'product_status' => 'active',
    'product_name' => 'Mauve Faceted Garden Stool End Table',
    'product_sku' => '433651',
    'brand_name' => 'Crate&Barrel',
    'price' => '65.97',
    'was_price' => '219',
    'image' => '/cnb/images/main/mauve-faceted-garden-stool-end-table.jpg',
    'LS_ID' => '826',
    'viewers' => 8,
  ),
  76 => 
  array (
    'id' => 58286,
    'product_description' => 'With its sleek silhouette and wide drawer, our Zola desk offers both a petite footprint and roomy storage, making it the perfect choice for small spaces. Its mid-century-modern appeal, gold-tipped tapered legs, matte gold hardware and walnut finish allow it to complement nearly any style.',
    'product_status' => 'active',
    'product_name' => 'Walnut and Gold Metal Zola Desk',
    'product_sku' => '556202',
    'brand_name' => 'World Market',
    'price' => '149.99',
    'was_price' => '149.99',
    'image' => '/nw/images/78626_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '660',
    'viewers' => 8,
  ),
  77 => 
  array (
    'id' => 49811,
    'product_description' => 'With clean, Parsons-style lines wrapped in luxe leather, our Folio side chair reinvents classic Italian design with comfort in mind. Top-grain, semi-aniline vegetable-tanned leather in dark graphite grey covers a supportive solid welded steel base and inviting foam-cushioned seat. ',
    'product_status' => 'active',
    'product_name' => 'Folio Green Top-Grain Leather Dining Chair',
    'product_sku' => '636024',
    'brand_name' => 'Crate&Barrel',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cnb/images/main/folio-green-top-grain-leather-dining-chair.jpg',
    'LS_ID' => '510',
    'viewers' => 8,
  ),
  78 => 
  array (
    'id' => 35391,
    'product_description' => 'Designed for indoor or outdoor use, our sculptural Cosmo Side Table is crafted
of lightweight, easy-care (but highly durable) aluminum, so it\'s easy to move
wherever you need it.

###### KEY DETAILS

  * 14.5"diam. x 18"h.
  * Spun aluminum in your choice of finish.
  * Indoor/outdoor.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Cosmo Side Table',
    'product_sku' => 'cosmo-side-table-antique-brass-h4029',
    'brand_name' => 'West Elm',
    'price' => '111.2-139',
    'was_price' => '139',
    'image' => '/westelm/westelm_images/cosmo-side-table-antique-brass-h4029_main.jpg',
    'LS_ID' => '226',
    'viewers' => 8,
  ),
  79 => 
  array (
    'id' => 36111,
    'product_description' => 'Brought to you in collaboration with Pottery Barn Kids, our GREENGUARD-
certified Gemini Nursery Collection combines unique design with durable
craftsmanship. Each piece is made from sustainably-sourced wood that\'s kiln-
dried for extra strength, complete with child-safe, water-based finishes. This
changing table has six roomy drawers and a topper that keeps changing pads
firmly in place. Best of all, the topper is easily removed when your diaper-
changing days are through.

###### KEY DETAILS

  * 48"w x 18"d x 36"h.
  * Solid and engineered wood.
  * All wood is sustainably-sourced and [FSC®-certified](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/).
  * Covered on all sides in a child-safe, water-based paint in White.
  * Metal legs in Light Bronze finish.
  * Six roomy drawers open on metal glides.
  * Removable topper secures with metal brackets (included). A layer of felt on the underside protects the top’s surface.
  * Use with our Changing Pad and Organic Matelasse Changing Pad Cover (both sold separately).
  * [GREENGUARD Gold Certified.](http://greenguard.org/en/CertificationPrograms/CertificationPrograms_childrenSchools.aspx) Meets or exceeds stringent chemical and VOC emissions standards.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * Tip kit included. Please read Safety Information tab for important safety callouts.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Gemini 6-Drawer Changing Table',
    'product_sku' => 'gemini-6-drawer-changing-table-h4323',
    'brand_name' => 'West Elm',
    'price' => '799.2',
    'was_price' => '999',
    'image' => '/westelm/westelm_images/gemini-6-drawer-changing-table-h4323_main.jpg',
    'LS_ID' => '933',
    'viewers' => 8,
  ),
  80 => 
  array (
    'id' => 35841,
    'product_description' => 'Designed to suit any style, our Anderson Dining Table\'s exaggerated, angular
legs add a modern edge to its clean-lined form. We love how the rich grain of
the acacia wood comes through the carob finish. Plus, its expandable design
seats up to 10, meaning making room for extra guests is no problem.

  * Expandable design has 3 width options: 40", 65", 90".
  * Solid acacia wood with a water-based Carob finish.
  * Natural variations in the wood grain and coloring are to be expected.
  * Table expands via two 25" drop-in leaves.
  * Leaves stored separately when not in use.
  * Includes apron gaps to accommodate for the natural shrinkage and expansion typical of solid wood.
  * Coordinates with west elm\'s Anderson Dining Bench (sold separately).
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Anderson Solid Wood Expandable Dining Table - Carob',
    'product_sku' => 'anderson-solid-wood-expandable-dining-table-carob-h2883',
    'brand_name' => 'West Elm',
    'price' => '1099',
    'was_price' => '1099',
    'image' => '/westelm/westelm_images/anderson-solid-wood-expandable-dining-table-carob-h2883_main.jpg',
    'LS_ID' => '552',
    'viewers' => 8,
  ),
  81 => 
  array (
    'id' => 36982,
    'product_description' => 'Mermelada Estudio takes a fresh look at everyday dining with this versatile, mixed-material table. Brass-finished steel sets a warm base with angular legs that taper down to the feet. Paired here with a clear, tempered glass top. Part of the mix and match collection, dining table top or base can be swapped out for a whole new look. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Harper Brass Dining Table with Glass Top',
    'product_sku' => '359011',
    'brand_name' => 'CB2',
    'price' => '936',
    'was_price' => '936',
    'image' => '/cb2/_images/main/harper-brass-dining-table-with-glass-top.jpg',
    'LS_ID' => '551',
    'viewers' => 7,
  ),
  82 => 
  array (
    'id' => 26362,
    'product_description' => 'The unique 3-in-1 design of the Babyletto Lolly Crib means it can transform from a crib into a daybed into a toddler bed with ease. It features gently contoured corners and a crisp white finish, plus spindles and feet that show off the natural grain of New Zealand Pine Wood. Designed for modern nurseries, the crib also includes a toddler rail for effortless conversion into a toddler bed.Learn how to choose a crib.',
    'product_status' => 'active',
    'product_name' => 'Babyletto Lolly White & Natural 3 in 1 Convertible Crib',
    'product_sku' => '452902',
    'brand_name' => 'Crate&Barrel',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cnb/images/main/babyletto-lolly-white-and-natural-3-in-1-convertible-crib.jpg',
    'LS_ID' => '942',
    'viewers' => 7,
  ),
  83 => 
  array (
    'id' => 52038,
    'product_description' => 'Extra-deep sofa designed by James Harrison is primed for lounging in lush grey velvet. Simple piping detail along the edges gives a modern, tailored look—a piece we love to style with tons of plush, textural throw pillows. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Valmar Grey Velvet Sofa',
    'product_sku' => '598048',
    'brand_name' => 'CB2',
    'price' => '1899',
    'was_price' => '1899',
    'image' => '/cb2/_images/main/valmar-grey-velvet-sofa.jpg',
    'LS_ID' => '201',
    'viewers' => 7,
  ),
  84 => 
  array (
    'id' => 97077,
    'product_description' => 'Part of our exclusive Jane Goodall Collection, this wildlife-themed playhouse was designed to spark kids\' curiosity about the natural world. A perfect gift for little adventurers and growing explorers, it\'s filled with details inspired by Jane Goodall\'s work and modeled after animal research centers. The printed artwork includes a chimpanzee in a chair, vegetation, a map, research equipment and more. Plus, there\'s a roll-up door flap in front and screened windows beneath the roof.',
    'product_status' => 'active',
    'product_name' => 'Jane Goodall Playhome',
    'product_sku' => '649866',
    'brand_name' => 'Crate&Barrel',
    'price' => '199',
    'was_price' => '199',
    'image' => '/cnb/img/JGPlayhouseSSF20web_pdp_main_carousel_med200918143300jane-goodall-playhome.jpgjane-goodall-playhome.jpg',
    'LS_ID' => '972',
    'viewers' => 7,
  ),
  85 => 
  array (
    'id' => 51273,
    'product_description' => 'Minimal in design, the Circa Sofa combines comfort with a modern silhouette.
With down-blend cushioning, it\'s also as comfortable as it is good looking.

###### KEY DETAILS

  * 91"w x 37.5"d x 28.75"h.
  * Iron frame in a matte Black finish.
  * Webbed cushion support.
  * Seat cushion filling: 60% fiber and 40% down.
  * Back cushion filling: 60% fiber and 40% down.
  * Seat and back cushions are attached.
  * Made in Mexico.',
    'product_status' => 'active',
    'product_name' => 'Circa Sofa',
    'product_sku' => 'voss-sofa-h6245',
    'brand_name' => 'West Elm',
    'price' => '1699-1799',
    'was_price' => '1699-1799',
    'image' => '/westelm/westelm_images/voss-sofa-h6245_main.jpg',
    'LS_ID' => '201',
    'viewers' => 7,
  ),
  86 => 
  array (
    'id' => 34870,
    'product_description' => 'We transformed our industrial-inspired Axel into a double-duty futon that
makes any room instantly guest-ready. Covered in your choice of leather, it
seats three when upright and sleeps two when folded down.

###### KEY DETAILS

  * 82.5"w x 39.5"d x 34.75"h.
  * Solid and engineered hardwood frame with reinforced joinery.
  * All wood is kiln-dried for added durability.
  * Available in your choice of genuine top-grain leather.
  * Metal legs in a Burnished Bronze finish.
  * Folds upright via a click clack mechanism.
  * Accommodates Full-sized fitted sheets (sold separately).
  * Seat firmness: Medium. On a scale from 1 to 5 (5 being firmest), we rate it a 4.
  * Made-to-Order options are made in Mexico; Stocked options are made in China.',
    'product_status' => 'active',
    'product_name' => 'Axel Full Leather Futon',
    'product_sku' => 'axel-futon-h3772',
    'brand_name' => 'West Elm',
    'price' => '2649-3299',
    'was_price' => '2649-3299',
    'image' => '/westelm/westelm_images/axel-futon-h3772_main.jpg',
    'LS_ID' => '201',
    'viewers' => 7,
  ),
  87 => 
  array (
    'id' => 36402,
    'product_description' => 'Talk about textural contrast: The curved back of our Porto Sofa is strung with
all-weather nylon cording set against a lofty mahogany wood base. Wrapped
around plush light gray pillows, it\'s seating that elevates your outdoor
space.

###### KEY DETAILS

  * 76"w x 33.3"d x 27.1"h.
  * Woven nylon cording in Dark Gray.
  * Kiln-dried mahogany wood base and legs.
  * Covered in a Driftwood finish.
  * Weather-resistant olefin cushions in Gray (included).
  * Seat cushion has high-resiliency polyurethane foam cores.
  * Back cushions are fiber filled.
  * Loose, non-reversible cushions with zip-off covers.
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Porto Outdoor Sofa (76")',
    'product_sku' => 'porto-outdoor-sofa-76-h5291',
    'brand_name' => 'West Elm',
    'price' => '1899',
    'was_price' => '1899',
    'image' => '/westelm/westelm_images/porto-outdoor-sofa-76-h5291_main.jpg',
    'LS_ID' => '801',
    'viewers' => 7,
  ),
  88 => 
  array (
    'id' => 58020,
    'product_description' => 'Designed for versatility, our made-to-order banquette assortment tailors to a
range of design aesthetics. Each collection includes a single, double, triple
and corner unit for endless configurations. They’re made to order with
competitive lead times, allowing you the choice of our in-line brand fabrics,
made-to-order contract-grade fabrics or COM, along with integrated power
options. Filled with a plush, polyester filling and made from a sturdy kiln-
dried frame to prevent warping, you\'ll be able to carve out dining space in
any room with our Novak Dining Banquette.

###### KEY DETAILS

  * Kiln-dried engineered wood legs.
  * Upholstered in 100% top grain leather in the color of your choice.
  * 100% polyester padding.
  * Legs feature adjustable levelers to adapt to varying floor levels.
  * Banquette component pieces connect together with the included legs/hardware.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. See more.
  * Assembled in the USA.',
    'product_status' => 'active',
    'product_name' => 'Novak Leather Banquette',
    'product_sku' => 'novak-leather-banquette-h6618',
    'brand_name' => 'West Elm',
    'price' => '799-1699',
    'was_price' => '799-1699',
    'image' => '/westelm/westelm_images/novak-leather-banquette-h6618_main.jpg',
    'LS_ID' => '511',
    'viewers' => 7,
  ),
  89 => 
  array (
    'id' => 48716,
    'product_description' => 'Our sculptural Tulip Pedestal Bistro Table works great in spaces both small
and large. With a spacious top, it\'s perfect for everything from impromptu
dinner parties to card games with friends.

  * Aluminum.
  * Suitable for indoor or outdoor use.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Tulip Pedestal Dining Table',
    'product_sku' => 'tulip-bistro-table-antique-rust-h3263',
    'brand_name' => 'West Elm',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/westelm/westelm_images/tulip-bistro-table-antique-rust-h3263_main.jpg',
    'LS_ID' => '551',
    'viewers' => 7,
  ),
  90 => 
  array (
    'id' => 35784,
    'product_description' => 'With its sculptural blackened brass base and concrete tabletop, our Tower
Dining Table blends rustic with refined to add uncomplicated style to your
space.

  * 72"w x 36"d x 31"h.
  * Concrete composite top.
  * Due to the materials used, natural variations will occur.
  * Stainless steel base in a Blackened Brass finish.
  * Hand-welded base.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Tower Dining Table - Concrete',
    'product_sku' => 'tower-dining-table-blackened-brass-h2542',
    'brand_name' => 'West Elm',
    'price' => '999',
    'was_price' => '999',
    'image' => '/westelm/westelm_images/tower-dining-table-blackened-brass-h2542_main.jpg',
    'LS_ID' => '551',
    'viewers' => 7,
  ),
  91 => 
  array (
    'id' => 34720,
    'product_description' => 'We gave our Portside Grand Sofa its rustic look by wire brushing the solid
wood frame and adding a weathered finish. We included yarn-dyed cushions for
the ultimate outdoor lounging spot.

###### KEY DETAILS

  * 119.6”w x 37.3"d x 30"h.
  * Moisture-resistant solid mahogany and solid eucalyptus wood with wire-brushed surface.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Yarn-dyed weather-resistant cushions in Gray (included).
  * Outdoor cover available (sold separately).
  * Frame made in Indonesia; cushions made in China.',
    'product_status' => 'active',
    'product_name' => 'Portside Outdoor Low 2-Piece Grand Sofa',
    'product_sku' => 'portside-outdoor-low-2-piece-grand-sofa-h5186',
    'brand_name' => 'West Elm',
    'price' => '2598',
    'was_price' => '2598',
    'image' => '/westelm/westelm_images/portside-outdoor-low-2-piece-grand-sofa-h5186_main.jpg',
    'LS_ID' => '801',
    'viewers' => 7,
  ),
  92 => 
  array (
    'id' => 35697,
    'product_description' => 'Available in two sizes, our Mid-Century Dining Table easily expands to
comfortably seat up to eight or ten, making it perfect for game nights, family
gatherings or dinner parties. Its sturdy frame is made from wood that\'s
certified to Forest Stewardship Council® (FSC) standards.

###### KEY DETAILS

  * Kiln-dried solid and engineered wood for extra durability.
  * Legs + runner: Solid ash wood.
  * Top: Ash wood veneer over engineered wood.
  * All wood is FSC®-certified, supporting forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Covered in a water-based Pebble finish.
  * Table expands via drop-in leaf.
  * Made in a [Fair Trade Certified™](https://www.westelm.com/pages/about-west-elm/our-commitments/fair-trade/) facility, directly benefiting the workers who make it.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Mid-Century Expandable Dining Table - Pebble',
    'product_sku' => 'mid-century-expandable-dining-table-pebble-h3996',
    'brand_name' => 'West Elm',
    'price' => '799',
    'was_price' => '799',
    'image' => '/westelm/westelm_images/mid-century-expandable-dining-table-pebble-h3996_main.jpg',
    'LS_ID' => '552',
    'viewers' => 7,
  ),
  93 => 
  array (
    'id' => 99897,
    'product_description' => 'Loved for its rustic farmhouse look, our sustainably sourced Emmerson® Dining Table exudes homey charm. A striking centerpiece for entertaining, this elegant table marries clean lines and simple construction with organic wood grain that features character-enhancing imperfections.',
    'product_status' => 'active',
    'product_name' => 'Emmerson® Reclaimed Wood Dining Table - Stone Gray',
    'product_sku' => 'emmerson-reclaimed-wood-dining-table-stone-gray-pine-h3942',
    'brand_name' => 'West Elm',
    'price' => '1199-1399',
    'was_price' => '1199-1399',
    'image' => '/westelm/westelm_images/emmerson-reclaimed-wood-dining-table-stone-gray-pine-h3942_main.jpg',
    'LS_ID' => '551',
    'viewers' => 7,
  ),
  94 => 
  array (
    'id' => 51883,
    'product_description' => 'skinny genes. Cool c-table that pulls right up to the sofa or bed is welded heavy-duty but looks light on its feet. Industrial iron with raw antiqued finish. mill c table is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Mill C Table',
    'product_sku' => '172898',
    'brand_name' => 'CB2',
    'price' => '129',
    'was_price' => '129',
    'image' => '/cb2/_images/main/mill-c-table.jpg',
    'LS_ID' => '226',
    'viewers' => 6,
  ),
  95 => 
  array (
    'id' => 25342,
    'product_description' => 'The faceted doors of this entryway cabinet feature a grid of pyramids, creating a three-dimensional facade of beautiful wood with a natural finish that showcases the grain. Keep your out-the-door essentials corralled and right where you need them in this stunning cabinet. Behind the doors are two adjustable shelves, perfect for holding baskets or bins filled with outdoor gear, dog-walking essentials, mail and more. Built-in cord cutouts make the modern hutch an ideal spot for concealing speakers. Make the most of entryways large and small with our exquisite Outline entryway cabinet, a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Outline Entryway Cabinet',
    'product_sku' => '429071',
    'brand_name' => 'Crate&Barrel',
    'price' => '799',
    'was_price' => '799',
    'image' => '/cnb/images/main/outline-entryway-cabinet.jpg',
    'LS_ID' => '232,430',
    'viewers' => 6,
  ),
  96 => 
  array (
    'id' => 26521,
    'product_description' => 'Take a regular old dresser. Add our changing table topper and pad. Presto change-o. You now have a clean, classic changing table.',
    'product_status' => 'active',
    'product_name' => 'White Changing Table Topper',
    'product_sku' => '546803',
    'brand_name' => 'Crate&Barrel',
    'price' => '149',
    'was_price' => '149',
    'image' => '/cnb/images/main/changing-table-topper-white.jpg',
    'LS_ID' => '935,99',
    'viewers' => 6,
  ),
  97 => 
  array (
    'id' => 49773,
    'product_description' => 'Curvy and cuddly, Calder embraces with sculptural style and a white vegan sheepskin that rivals the look and feel of the real thing. The chair\'s in-the-round styling flows a sheltering arc from arm to back, floating a circular seat. Striking on its own, Calder makes even more impact paired with a sofa or flanking an end table. The trend-setting Calder chair is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Calder Chair',
    'product_sku' => '567486',
    'brand_name' => 'Crate&Barrel',
    'price' => '999',
    'was_price' => '999',
    'image' => '/cnb/images/main/calder-chair.jpg',
    'LS_ID' => '210',
    'viewers' => 6,
  ),
  98 => 
  array (
    'id' => 49713,
    'product_description' => 'Versatile design mixes modern materials for a fresh take on everyday dining. Designed by Mermelada Estudio, brass-finished steel sets a warm base with angled legs that taper to the feet. Layered here with a cool slab of black marble with wisps of grey and white veining. Part of the mix-and-match collection, dining table top or base can be swapped out for a whole new look. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Harper Brass Dining Table with Black Marble Top',
    'product_sku' => '580162',
    'brand_name' => 'CB2',
    'price' => '1849',
    'was_price' => '1599',
    'image' => '/cb2/_images/main/harper-brass-dining-table-with-black-marble-top.jpg',
    'LS_ID' => '551',
    'viewers' => 6,
  ),
  99 => 
  array (
    'id' => 27968,
    'product_description' => 'Hayes\' open fretwork of angled brass tubing finds its perfect complement in beautifully grained solid acacia wood and acacia veneer. This sideboard\'s mixed materials expand upon the trend with an elevated design that feels fresh and modern in any setting. Double doors open to a divided cabinet with two adjustable shelves, while the single door opens to one shelf. The Hayes Acacia Sideboard is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Hayes Acacia Sideboard',
    'product_sku' => '586097',
    'brand_name' => 'Crate&Barrel',
    'price' => '1399',
    'was_price' => '1399',
    'image' => '/cnb/images/main/hayes-acacia-sideboard.jpg',
    'LS_ID' => '534',
    'viewers' => 6,
  ),
  100 => 
  array (
    'id' => 100018,
    'product_description' => 'Designed in partnership with the Jane Goodall Institute, this play chair features a bold, wildlife-inspired spot print. Perfect for any young animal lover, it\'ll bring a touch of adventurous, safari-ready style into the kids room or playroom. With a seat crafted from rubberwood and metal steel tube legs, this comfy printed kids chair is durable enough for hours of playtime, reading and home learning. Mix and match with the rest of our Jane Goodall Play Chairs.',
    'product_status' => 'active',
    'product_name' => 'Printed Play Chair Animal Print',
    'product_sku' => '659268',
    'brand_name' => 'Crate&Barrel',
    'price' => '79',
    'was_price' => '79',
    'image' => '/cnb/images/main/printed-play-chair-animal-print.jpg',
    'LS_ID' => '911,99',
    'viewers' => 6,
  ),
  101 => 
  array (
    'id' => 35658,
    'product_description' => 'This shelf is certainly a statement piece, but functionality is definitely not
sacrificed because of it. Great for decorating your bathroom and pairing with
West Elm\'s many bath accessories.

###### KEY DETAILS

  * 30"w x 3.5"d x 30"h.
  * Iron in Antique Brass finish.
  * Hanging hardware included.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Deco Round Metal Shelf',
    'product_sku' => 'deco-round-metal-shelf-d7582',
    'brand_name' => 'West Elm',
    'price' => '120',
    'was_price' => '120',
    'image' => '/westelm/westelm_images/deco-round-metal-shelf-d7582_main.jpg',
    'LS_ID' => '231',
    'viewers' => 6,
  ),
  102 => 
  array (
    'id' => 97064,
    'product_description' => 'Designed for cafes and restaurants, this durable and lofty bar table is a
versatile spot to dine and socialize. Its compact size has space for up to two
stools, while each table base is smartly equipped with adjustable glides that
adapt to varying floor levels—making the dreaded table-wobble a thing of the
past.

###### KEY DETAILS

  * 24"diam. x 42"h.
  * This contract grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * White Carrara marble veneer over an engineered wood top.
  * Top features a water-based sealant.
  * Rust-proof metal base in Antique Bronze and Blackened Brass finishes.
  * Base includes built-in levelers.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'White Marble Bar Table',
    'product_sku' => 'white-marble-counter-table-h4186',
    'brand_name' => 'West Elm',
    'price' => '559',
    'was_price' => '559',
    'image' => '/westelm/westelm_images/white-marble-counter-table-h4186_main.jpg',
    'LS_ID' => '551',
    'viewers' => 6,
  ),
  103 => 
  array (
    'id' => 49585,
    'product_description' => 'Shaped like a shield with a golden rim, these decorative modern takes on
royal-inspired mirrors look great on any wall.

###### KEY DETAILS

  * Stainless steel in a Gold finish.
  * Mirrored glass.
  * Hangs vertically.
  * Mounting hardware included.
  * Sold individually.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Florence Shield Wall Mirrors',
    'product_sku' => 'florence-shield-wall-mirrors-d8551',
    'brand_name' => 'West Elm',
    'price' => '269.1',
    'was_price' => '299',
    'image' => '/westelm/westelm_images/florence-shield-wall-mirrors-d8551_main.jpg',
    'LS_ID' => '1110',
    'viewers' => 6,
  ),
  104 => 
  array (
    'id' => 35793,
    'product_description' => 'Our best-selling Slope Stool comes in a range of modern fabrics and finishes.
Standing on a sturdy metal frame, its curved seat and back offer a comfy place
to sit and linger.',
    'product_status' => 'active',
    'product_name' => 'Slope Upholstered Bar & Counter Stools',
    'product_sku' => 'slope-upholstered-bar-counter-stools-h4010',
    'brand_name' => 'West Elm',
    'price' => '279-349',
    'was_price' => '279-349',
    'image' => '/westelm/westelm_images/slope-upholstered-bar-counter-stools-h4010_main.jpg',
    'LS_ID' => '512',
    'viewers' => 6,
  ),
  105 => 
  array (
    'id' => 35695,
    'product_description' => 'Offering plenty of room to grow, our Mid-Century Rounded Expandable Dining
Table adds extra seats when extended, making it perfect for family meals and
dinner parties alike. Its sturdy contract-grade frame is crafted from
sustainably sourced wood and made in a Fair Trade Certified™ facility.',
    'product_status' => 'active',
    'product_name' => 'Mid-Century Rounded Expandable Dining Table',
    'product_sku' => 'mid-century-expandable-dining-table-round-h4230',
    'brand_name' => 'West Elm',
    'price' => '699-799',
    'was_price' => '699-799',
    'image' => '/westelm/westelm_images/mid-century-expandable-dining-table-round-h4230_main.jpg',
    'LS_ID' => '552',
    'viewers' => 6,
  ),
  106 => 
  array (
    'id' => 35621,
    'product_description' => 'A sophisticated approach to storage, our Foundry Collection lays a stylish
foundation with its streamlined frame and FSC®-certified wood. This console &
bookcase set is equipped with roomy cabinets and plenty of shelving to display
(or hide away) books, electronics and more.

###### KEY DETAILS

  * 117.1"w x 15.75"d x 83.5"h.
  * This contract grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Kiln-dried solid mahogany & engineered wood.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Covered in a water-based Dark Walnut finish.
  * Metal legs & accents in an Antique Bronze finish.
  * Set includes one 81" Media Console and two Narrow Bookcases.
  * Console: Four doors open to reveal two cabinets.
  * Bookcase: One door opens to reveal a cabinet with two adjustable shelves.
  * Legs feature adjustable levelers to adapt to varying floor levels.
  * Anti-tip kit hardware (included) is highly recommended to provide protection against tipping of furniture.
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Foundry Narrow Bookcase & 81.5" Console Set - Dark Walnut',
    'product_sku' => 'foundry-narrow-bookcase-815-console-set-dark-walnut-h4326',
    'brand_name' => 'West Elm',
    'price' => '3197',
    'was_price' => '3197',
    'image' => '/westelm/westelm_images/foundry-narrow-bookcase-815-console-set-dark-walnut-h4326_main.jpg',
    'LS_ID' => '430',
    'viewers' => 6,
  ),
  107 => 
  array (
    'id' => 26807,
    'product_description' => 'Petrie is a distinct living room sofa that sits at the intersection of mid-century and today, with clean lines and tailored cushions expertly button-tufted by hand. ',
    'product_status' => 'active',
    'product_name' => 'Petrie Midcentury Sofa',
    'product_sku' => '106389',
    'brand_name' => 'Crate&Barrel',
    'price' => '1899',
    'was_price' => '1899',
    'image' => '/cnb/images/main/petrie-midcentury-sofa.jpg',
    'LS_ID' => '201',
    'viewers' => 5,
  ),
  108 => 
  array (
    'id' => 25942,
    'product_description' => 'Stylish and contemporary Lowe wraps the classic Parsons-style chair in pure color. With a roomy cushioned seat and back, it\'s designed for lingering at almost any style dining room table. ',
    'product_status' => 'active',
    'product_name' => 'Lowe Khaki Upholstered Dining Chair',
    'product_sku' => '306933',
    'brand_name' => 'Crate&Barrel',
    'price' => '199',
    'was_price' => '179',
    'image' => '/cnb/images/main/lowe-khaki-fabric-side-chair.jpg',
    'LS_ID' => '510,99',
    'viewers' => 5,
  ),
  109 => 
  array (
    'id' => 27795,
    'product_description' => 'Inspired by traditional Chinese design, this modern design classic goes all natural for sophisticated dining. Fashioned with its signature Y-back and semi-circular arms, the dining chair gets a fresh dose of texture with a woven synthetic wicker seat on a frame of black-finished teak.',
    'product_status' => 'active',
    'product_name' => 'Crescent Black Rush Seat Dining Chair',
    'product_sku' => '319130',
    'brand_name' => 'Crate&Barrel',
    'price' => '349',
    'was_price' => '349',
    'image' => '/cnb/images/main/crescent-black-rush-seat-dining-chair.jpg',
    'LS_ID' => '510',
    'viewers' => 5,
  ),
  110 => 
  array (
    'id' => 25368,
    'product_description' => 'A blend of natural materials and classic design creates this beautiful, mid-century-inspired collection. FSC-certified teak and teak veneer frames showcase doors inset with panels of handwoven natural rattan for remote control signal access. ',
    'product_status' => 'active',
    'product_name' => 'Blake Grey Wash 68" Media Console',
    'product_sku' => '328293',
    'brand_name' => 'Crate&Barrel',
    'price' => '1499',
    'was_price' => '1499',
    'image' => '/cnb/images/main/blake-grey-wash-68-media-console.jpg',
    'LS_ID' => '233,430,99',
    'viewers' => 5,
  ),
  111 => 
  array (
    'id' => 36986,
    'product_description' => 'Versatile design mixes modern materials for a fresh take on everyday dining. Designed by Mermelada Estudio, brass-finished steel sets a warm base with angled legs that taper to the feet. Layered here with a cool slab of white marble. Part of the mix and match collection, dining table top or base can be swapped out for a whole new look. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Harper Brass Dining Table with Marble Top',
    'product_sku' => '359001',
    'brand_name' => 'CB2',
    'price' => '1849',
    'was_price' => '1599',
    'image' => '/cb2/_images/main/harper-brass-dining-table-with-marble-top.jpg',
    'LS_ID' => '551',
    'viewers' => 5,
  ),
  112 => 
  array (
    'id' => 36987,
    'product_description' => 'Designed by Mermelada Estudio, versatile table mixes modern materials for a fresh take on everyday dining. Solid steel in a warm brass finish sets a clean base with angled legs that taper to the feet. Paired here with an industrial grey concrete top. Part of the mix and match collection, top or base can be swapped out for a whole new look. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Harper Brass Dining Table with Concrete Top',
    'product_sku' => '359066',
    'brand_name' => 'CB2',
    'price' => '1349',
    'was_price' => '1199',
    'image' => '/cb2/_images/main/harper-brass-dining-table-with-concrete-top.jpg',
    'LS_ID' => '551',
    'viewers' => 5,
  ),
  113 => 
  array (
    'id' => 25429,
    'product_description' => 'Inlaid by hand with veined and variegated green marble, Bradshaw is a jewel of a coffee table. Surrounded by a faceted frame of brushed grey mango wood and mango veneer, the top stands on four slim legs, also finished in brushed grey. Made for us exclusively, the Bradshaw coffee table graces the room with its sophisticated mix of materials.',
    'product_status' => 'active',
    'product_name' => 'Bradshaw Green Marble Coffee Table',
    'product_sku' => '435720',
    'brand_name' => 'Crate&Barrel',
    'price' => '199.97',
    'was_price' => '469',
    'image' => '/cnb/images/main/bradshaw-green-marble-coffee-table.jpg',
    'LS_ID' => '225',
    'viewers' => 5,
  ),
  114 => 
  array (
    'id' => 50413,
    'product_description' => 'A solid rubberwood construction and antique gray finish make our Indonesian daybed frame a durable and stylish addition to your home. With two turned legs in the front, an architectural backrest and beautiful curved arms, this daybed will enhance your space as a cozy spot to read or nap.',
    'product_status' => 'active',
    'product_name' => 'Indonesian Daybed Frame',
    'product_sku' => '449170',
    'brand_name' => 'World Market',
    'price' => '449.99',
    'was_price' => '449.99',
    'image' => '/nw/images/18579_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '206',
    'viewers' => 5,
  ),
  115 => 
  array (
    'id' => 51568,
    'product_description' => 'Black boucle chair by Caleb Zipperer brings lounge to the table. Architectural in profile and plush all around, seat sits deep with a rounded back for extra comfort. Expect guests to stay a while. Learn more about Caleb Zipperer on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Stature Chair Black',
    'product_sku' => '466334',
    'brand_name' => 'CB2',
    'price' => '329',
    'was_price' => '329',
    'image' => '/cb2/_images/main/stature-chair-black.jpg',
    'LS_ID' => '510,99,210',
    'viewers' => 5,
  ),
  116 => 
  array (
    'id' => 49706,
    'product_description' => 'Honed white marble, veined with wisps of black and grey, tops a ribbed iron base finished in antiqued silver. Marble table top is trimmed with a flush metal ring in the same antiqued silver—a design detail we love. Dresses way up with formal dining chairs, goes more casual with contrasting leather/metal seating, or stands stunning alone to welcome visitors in the entry. Seats six easily. Learn more about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Cypher White Marble DIning Table',
    'product_sku' => '579875',
    'brand_name' => 'CB2',
    'price' => '1699.15',
    'was_price' => '1899',
    'image' => '/cb2/_images/main/cypher-white-marble-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 5,
  ),
  117 => 
  array (
    'id' => 52051,
    'product_description' => 'A modern (and a little glam) take on a classic cane chair. Jannis Ellenberger pairs natural rattan with a crisp white frame and plush ivory boucle seat. An easy way to bring high-end style to the dining or living room. Learn more about Jannis Ellenberger on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Nadia White Cane Chair',
    'product_sku' => '580647',
    'brand_name' => 'CB2',
    'price' => '379',
    'was_price' => '349',
    'image' => '/cb2/_images/main/nadia-white-cane-chair.jpg',
    'LS_ID' => '510,99,210',
    'viewers' => 5,
  ),
  118 => 
  array (
    'id' => 49922,
    'product_description' => 'Exquisite solid American black walnut takes the finest form in Apex, with a slim reverse-beveled top that balances on a sculptural, crisscrossed base of notched and angled beams. ',
    'product_status' => 'active',
    'product_name' => 'Apex 64" Round Dining Table',
    'product_sku' => '583007',
    'brand_name' => 'Crate&Barrel',
    'price' => '2499',
    'was_price' => '2499',
    'image' => '/cnb/images/main/apex-64-round-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 5,
  ),
  119 => 
  array (
    'id' => 35222,
    'product_description' => 'Our take on Italian mid-century design, the Phoebe Chair\'s soft-edged, curvy
form—a rounded back and low-lying arms—lets you lounge in complete comfort,
without sacrificing space. Its Contract Grade frame means that it\'s extra
sturdy.',
    'product_status' => 'active',
    'product_name' => 'Phoebe Chair - Metal Legs',
    'product_sku' => 'phoebe-chair-h2643',
    'brand_name' => 'West Elm',
    'price' => '499-749',
    'was_price' => '499-749',
    'image' => '/westelm/westelm_images/phoebe-chair-h2643_main.jpg',
    'LS_ID' => '210',
    'viewers' => 5,
  ),
  120 => 
  array (
    'id' => 35021,
    'product_description' => 'Casual and coastal-inspired, our Newport Sofa boasts clean lines and deep
seats with an airy but durable kiln-dried wooden platform. Choose from toss
back cushions for customizable comfort or boxed back cushions for a more
tailored look.

###### KEY DETAILS

  * Solid pine and engineered hardwood frame with reinforced joinery.
  * Ash wood legs (removable) in a Pecan or Almond finish.
  * All wood is kiln-dried for added durability.
  * Back & seat cushions are 70% poly fiber, 30% duck feather blend in down proof ticking.
  * Seat firmness: Soft. On a scale from 1 to 5 (5 being firmest), we rate it a 2.
  * Webbed seat and back support.
  * Loose, reversible cushions with zip-off covers.
  * Your choice of two boxed or four toss back cushions (included).
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Newport Sofa',
    'product_sku' => 'newport-sofa-84-h5007',
    'brand_name' => 'West Elm',
    'price' => '1599-1999',
    'was_price' => '1599-1999',
    'image' => '/westelm/westelm_images/newport-sofa-84-h5007_main.jpg',
    'LS_ID' => '201',
    'viewers' => 5,
  ),
  121 => 
  array (
    'id' => 34949,
    'product_description' => 'True to its name, the Haven Loft Sofa is one you\'ll want to spend lots of time
in. Its deep, comfy seat and low, padded arms give it an incredible sink-
right-in quality that\'s perfect for the whole family. This lofted version
stands on shapely wooden legs.

###### KEY DETAILS

  * Hand-built frames with hand-finished upholstery.
  * Engineered hardwood frame with mortise & tenon joinery.
  * Rubberwood legs in a Pecan finish.
  * All wood is kiln-dried to prevent warping.
  * High-gauge sinuous springs provide cushion support.
  * Seat cushions have down alternative-wrapped, high-resiliency polyurethane foam cores.
  * Seat firmness: Soft. On a scale from 1 to 5 (5 being firmest), we rate it a 2.
  * Back cushions are filled with a down alternative and poly fiber blend.
  * Cushions are reversible (Astor Velvet excluded).
  * Removable legs feature adjustable levelers to adapt to varying floor levels.
  * Assembled in the USA.
  * Make sure it fits! See our guide on [measuring for delivery](http://www.westelm.com/shop/design-lab/measuring-delivery-design-lab).',
    'product_status' => 'active',
    'product_name' => 'Haven Loft Sofa',
    'product_sku' => 'haven-loft-sofa-86-h4998',
    'brand_name' => 'West Elm',
    'price' => '959.2-2998',
    'was_price' => '1039.2-2998',
    'image' => '/westelm/westelm_images/haven-loft-sofa-86-h4998_main.jpg',
    'LS_ID' => '201',
    'viewers' => 5,
  ),
  122 => 
  array (
    'id' => 35300,
    'product_description' => 'Our Origami Coffee Table has a hand-inlaid bone-tile top that\'s artfully
supported by a sculptural base. Each of these statement-making pieces is
crafted in a Fair Trade Certified™ facility, directly improving the life of
the artisan who makes it.',
    'product_status' => 'active',
    'product_name' => 'Origami Coffee Table',
    'product_sku' => 'origami-coffee-table-g672',
    'brand_name' => 'West Elm',
    'price' => '399-399.2',
    'was_price' => '399-499',
    'image' => '/westelm/westelm_images/origami-coffee-table-g672_main.jpg',
    'LS_ID' => '225',
    'viewers' => 5,
  ),
  123 => 
  array (
    'id' => 35431,
    'product_description' => 'Made from richly grained solid mango wood, our Industrial Storage Console is
lofted on airy steel legs and has two deep drawers and an open bottom shelf to
keep spaces looking neat. It\'s made in a Fair Trade Certified™ facility,
directly benefitting the artisans who make it.',
    'product_status' => 'active',
    'product_name' => 'Industrial Storage Console',
    'product_sku' => 'rustic-storage-console-g570',
    'brand_name' => 'West Elm',
    'price' => '499',
    'was_price' => '499',
    'image' => '/westelm/westelm_images/rustic-storage-console-g570_main.jpg',
    'LS_ID' => '227',
    'viewers' => 5,
  ),
  124 => 
  array (
    'id' => 36241,
    'product_description' => 'Inspired by old factory spaces in Brooklyn, this iron-and-mango wood floor
mirror adds industrial style to rooms.

  * 30"w x 1.5"d x 72"h.
  * Iron and mango wood frame.
  * Mirrored glass.
  * May be leaned against a wall; tip kit hardware included.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Industrial Metal & Wood Framed Floor Mirror',
    'product_sku' => 'industrial-floor-mirror-w1610',
    'brand_name' => 'West Elm',
    'price' => '499',
    'was_price' => '499',
    'image' => '/westelm/westelm_images/industrial-floor-mirror-w1610_main.jpg',
    'LS_ID' => '1110',
    'viewers' => 5,
  ),
  125 => 
  array (
    'id' => 34755,
    'product_description' => 'Our Mid-Century Grand Nightstand is bedside storage that\'s built to last. Made
in a Fair Trade Certified™ facility, its sturdy frame is made from kiln-dried,
sustainably sourced wood and covered in water-based finishes. Choose from the
standard or charging version, which features two USB sockets and power
outlets.',
    'product_status' => 'active',
    'product_name' => 'Mid-Century Grand Nightstand - Acorn',
    'product_sku' => 'mid-century-nightstand-grand-h2061',
    'brand_name' => 'West Elm',
    'price' => '499-1098',
    'was_price' => '499-1098',
    'image' => '/westelm/westelm_images/mid-century-nightstand-grand-h2061_main.jpg',
    'LS_ID' => '335',
    'viewers' => 5,
  ),
  126 => 
  array (
    'id' => 34747,
    'product_description' => 'Inspired by Scandinavian modernism, our Modern Wall Desk pairs a sleek body
with a pecan-finished frame and beautifully-angled legs. Its wide desktop offers plenty of workspace while drawers and shelves help keep everything
organized.

  * 46"w x 20"d x 75"h.
  * Pecan-finished solid wood legs.
  * White lacquered engineered wood body.
  * Metal hardware in a Gunmetal finish.
  * 1 drawer and 2 fixed shelves.
  * Anti-tip kit hardware (included) is highly recommended to provide protection against tipping of furniture.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Modern Wall Desk',
    'product_sku' => 'modern-wall-desk-white-pecan-h1499',
    'brand_name' => 'West Elm',
    'price' => '999',
    'was_price' => '999',
    'image' => '/westelm/westelm_images/modern-wall-desk-white-pecan-h1499_main.jpg',
    'LS_ID' => '660',
    'viewers' => 5,
  ),
  127 => 
  array (
    'id' => 57857,
    'product_description' => 'Curved in the seat and the back for an extra inviting touch, our Uma Dining
Chair serves up comfort and style. It\'s covered in durable upholstery fabric
that mimics the look of linen.

###### KEY DETAILS

  * 18.5"w x 22"d x 32.5"h.
  * Linen weave (85% polyester, 15% cotton) upholstery in Dark Horseradish.
  * Solid wood legs; bentwood frame.
  * Felt foot caps protect floors.
  * Sold as a set of 2.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Uma Upholstered Dining Chair (Set of 2)',
    'product_sku' => 'uma-upholstered-dining-chair-h4662',
    'brand_name' => 'West Elm',
    'price' => '279',
    'was_price' => '279',
    'image' => '/westelm/westelm_images/uma-upholstered-dining-chair-h4662_main.jpg',
    'LS_ID' => '510',
    'viewers' => 5,
  ),
  128 => 
  array (
    'id' => 35298,
    'product_description' => 'Naturally occurring color variations, beautifully winding wood grains and
fresh raw edges give our Harbor Live Edge Coffee Table its unique look.
Resting atop beautifully complementary Y-shaped steel legs, it\'s the perfect
home for drinks, books and accessories. The raw construction of this piece
will make each piece subtly one of a kind.

###### KEY DETAILS

  * Kiln-dried Trembesi wood top in a stained Toffee finish.
  * Steel legs in an Antique Bronze finish.
  * Legs feature adjustable levelers to adapt to varying floor levels.
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Harbor Live Edge Coffee Table',
    'product_sku' => 'harbor-live-edge-coffee-table-h4927',
    'brand_name' => 'West Elm',
    'price' => '238.99',
    'was_price' => '399',
    'image' => '/westelm/westelm_images/harbor-live-edge-coffee-table-h4927_main.jpg',
    'LS_ID' => '225',
    'viewers' => 5,
  ),
  129 => 
  array (
    'id' => 36044,
    'product_description' => 'Dressed up with an antique brass finish, the clean lines of our Box Frame Bed
Frame means that it sits seamlessly with any style, or use it on its own as a
simple platform bed.

  * Metal frame in an Antique Brass finish.
  * Solid + engineered wood bed slats included.
  * Accommodates most standard mattresses; box spring optional.
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Box Frame Bed Frame - Antique Brass',
    'product_sku' => 'box-frame-bed-frame-antique-brass-h3703',
    'brand_name' => 'West Elm',
    'price' => '449-549',
    'was_price' => '449-549',
    'image' => '/westelm/westelm_images/box-frame-bed-frame-antique-brass-h3703_main.jpg',
    'LS_ID' => '300',
    'viewers' => 5,
  ),
  130 => 
  array (
    'id' => 51232,
    'product_description' => 'Inspired by 1950s furniture silhouettes, our Hamilton Sectional feels as
luxurious as it looks with its down-wrapped back cushions. It\'s perched on
solid poplar wood legs and covered in your choice of genuine or vegan leather.

###### KEY DETAILS

  * 120"w x 98"d x 32"h.
  * Available in your choice of genuine top-grain leather or animal-friendly vegan leather.
  * Solid and engineered hardwood frame with reinforced joinery.
  * Solid wood legs in an Almond finish.
  * All wood is kiln dried for added durability.
  * Webbed seat and back support.
  * Seat cushions have fiber-wrapped, high-resiliency polyurethane foam cores.
  * Seat firmness: Medium. On a scale from 1 to 5 (5 being firmest), we rate it a 3.
  * Back cushions have poly fiber fill.
  * Loose, nonreversible cushions with zip-off covers.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Hamilton Leather 5-Piece U-Shaped Sectional',
    'product_sku' => 'hamilton-5-piece-u-shaped-sectional-h6217',
    'brand_name' => 'West Elm',
    'price' => '9356-12695',
    'was_price' => '9356-12695',
    'image' => '/westelm/westelm_images/hamilton-5-piece-u-shaped-sectional-h6217_main.jpg',
    'LS_ID' => '202',
    'viewers' => 5,
  ),
  131 => 
  array (
    'id' => 36063,
    'product_description' => 'Brought to you in collaboration with Pottery Barn Kids, our GREENGUARD-
certified Gemini Nursery Collection combines unique design with durable
craftsmanship. Each piece is made from wood that\'s kiln-dried for extra
strength, complete with child-safe, water-based finishes. This crib grows with
your baby, featuring two platform height options, and can be converted into a
cozy toddler bed with the matching conversion kit (sold separately). Two
bottom drawers store extra bedding, clothes or toys.

###### KEY DETAILS

  * Solid poplar and engineered wood.
  * Covered in a child-safe, water-based paint in White.
  * Metal mattress platform has height options to accommodate your growing baby (mattress sold separately).
  * Two storage drawers.
  * [GREENGUARD Gold Certified.](http://greenguard.org/en/CertificationPrograms/CertificationPrograms_childrenSchools.aspx) Meets or exceeds stringent chemical and VOC emissions standards.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * Please read Safety Information tab for important crib safety callouts.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Gemini Convertible Storage Crib - White',
    'product_sku' => 'gemini-convertible-storage-crib-white-d6751',
    'brand_name' => 'West Elm',
    'price' => '119.2-639.2',
    'was_price' => '119.2-799',
    'image' => '/westelm/westelm_images/gemini-convertible-storage-crib-white-d6751_main.jpg',
    'LS_ID' => '942',
    'viewers' => 5,
  ),
  132 => 
  array (
    'id' => 35750,
    'product_description' => 'Creative firm Roar & Rabbit designs textiles, furniture and home accessories
that blend modern style with whimsical details. We worked with them to create
this Geo Inlay Dresser. Shiny brass-finished metal is inlaid into wood for a
modern, luxe look with tons of geometric texture and subtle shine.

  * Created in collaboration with Roar & Rabbit. [Learn more](/shop/collaborations/roar-rabbit/?cm_type=lnav).
  * 32"w x 16"d x 34"h.
  * Solid raw mango wood.
  * Shiny Brass-finished metal details and legs.
  * Made in a Fair Trade Certified™ facility.
  * [Learn more](https://www.westelm.com/pages/about-west-elm/our-commitments/fair-trade/) about the positive impact your purchase has on Fair Trade workers and their communities.
  * Wood finish is natural and may vary slightly.
  * Drawers open from the side on angled metal glides.
  * Anti-tip kit hardware (included) is highly recommended to provide protection against tipping of furniture.
  * The mango wood used on this product is sustainably sourced from trees that no longer produce fruit. 
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Roar & Rabbit™ Brass Geo Inlay 3-Drawer Dresser - Raw Mango',
    'product_sku' => 'roar-rabbit-brass-geo-inlay-3-drawer-dresser-h1807',
    'brand_name' => 'Roar + Rabbit',
    'price' => '999',
    'was_price' => '999',
    'image' => '/westelm/westelm_images/roar-rabbit-brass-geo-inlay-3-drawer-dresser-h1807_main.jpg',
    'LS_ID' => '334',
    'viewers' => 5,
  ),
  133 => 
  array (
    'id' => 36532,
    'product_description' => 'We did the styling for you. The popular Silhouette Pedestal Dining Table is
now available with our Finley Dining Chairs for a seamless look. Our
Silhouette Pedestal Dining Table cuts a fine figure with its richly-variegated
marble top perched on a dramatic metal base. Made from solid marble sourced
from India, the natural variations in veining mean that no two tables are
exactly alike. Plus, the sophisticated Finley chairs make for a perfect
pairing.

###### KEY DETAILS

  * Includes: 1 Silhouette Pedestal 44" Dining Table and 4 Finley Dining Chairs or 1 Silhouette Pedestal 60" Dining Table and 6 Finley Dining Chairs.

SILHOUETTE PEDESTAL ROUND DINING TABLE

  * Solid marble top.
  * Marble comes with a permeating sealer.
  * Metal base in an Antique Brass finish.
  * Each marble slab is unique; exact color and veining will vary.
  * Made in India.
  * Patent pending.

FINLEY DINING CHAIRS

  * 19.7"w x 22.8"d x 33.3"h.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](http://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Upholstered with Distressed Velvet (63% polyester, 37% recycled polyester) in Light Taupe.
  * Metal legs.
  * Attached seat cushion.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Silhouette Pedestal Round Dining Table & Finley Chair Set',
    'product_sku' => 'silhouette-pedestal-round-dining-table-finley-chair-set-h5045',
    'brand_name' => 'West Elm',
    'price' => '2198-3398',
    'was_price' => '2198-3398',
    'image' => '/westelm/westelm_images/silhouette-pedestal-round-dining-table-finley-chair-set-h5045_main.jpg',
    'LS_ID' => '500S',
    'viewers' => 5,
  ),
  134 => 
  array (
    'id' => 97270,
    'product_description' => 'designer lounge. Classic yet surprising, minimalist yet intricate, Filaki elevates any outdoor space. Designed by Jannis Ellenberger, the natural beauty of mahogany wood accents interesting design details like a slatted base and angled legs. Contrasting black and white stripe cushion in weather-resistant polyester adds a touch of glamour to any outdoor decor. Best of all, cushion cover zips right off for easy cleaning. Learn more about Jannis Ellenberger on our blog. filaki lounger with black and white stripe cushion is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Filaki Lounger with Black and White Stripe Cushion',
    'product_sku' => '151121',
    'brand_name' => 'CB2',
    'price' => '849',
    'was_price' => '829',
    'image' => '/cb2/_images/main/filaki-lounger-with-black-and-white-stripe-cushion.jpg',
    'LS_ID' => '210',
    'viewers' => 4,
  ),
  135 => 
  array (
    'id' => 27729,
    'product_description' => 'Combining the rugged look of concrete with the natural beauty of acacia wood, our Caicos dining table looks clean and casual. With its simple lines and roomy trestle base, the versatile table partners with a wide range of dining chairs to seat six. Made of sustainably sourced acacia wood tinted grey, the base is masterfully crafted with mortise-and-tenon joinery and hand finishing to make each table unique. Likewise, each tabletop exhibits its own markings and color nuance inherent to poly cement, a mix of concrete and resin. ',
    'product_status' => 'active',
    'product_name' => 'Caicos Cement Top Dining Table',
    'product_sku' => '314128',
    'brand_name' => 'Crate&Barrel',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/cnb/images/main/caicos-cement-top-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 4,
  ),
  136 => 
  array (
    'id' => 51784,
    'product_description' => 'roped in. Impromptu seating for social circles. Ropey bands of boiled wool are twisted and wrapped in a mesmerizing coil with soft, organic texture. Dense poly-filled round is substantial for seat/ottoman duty. wool wrap natural pouf is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Wool Wrap Natural Pouf',
    'product_sku' => '330294',
    'brand_name' => 'CB2',
    'price' => '129',
    'was_price' => '129',
    'image' => '/cb2/_images/main/wool-wrap-pouf.jpg',
    'LS_ID' => '222',
    'viewers' => 4,
  ),
  137 => 
  array (
    'id' => 51813,
    'product_description' => 'Modern, unadorned and completely functional. Rectangular coffee table spotlights the beauty of exotic olive wood with a super hi-gloss finish. Thoughtfully designed by Leonhard Pfeifer, the table\'s "negative space plays as much importance as the positive" with the open frame leaving plenty of room for storage underneath. Learn about Leonhard Pfeifer on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Reyes High-Gloss Olive Wood Coffee Table',
    'product_sku' => '342765',
    'brand_name' => 'CB2',
    'price' => '399',
    'was_price' => '799',
    'image' => '/cb2/_images/main/reyes-high-gloss-olive-wood-coffee-table.jpg',
    'LS_ID' => '225',
    'viewers' => 4,
  ),
  138 => 
  array (
    'id' => 37093,
    'product_description' => 'A sleeper sofa that\'s actually stylish? Yes, please. Convertible seater/sleeper is the am/pm solution for guests and small-space dwellers. Ultra-sophisticated in black upholstery and metal frame, you\'d never guess it flips to a queen-size bed at the end of the day. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Flex Black Sleeper Sofa',
    'product_sku' => '356748',
    'brand_name' => 'CB2',
    'price' => '1099',
    'was_price' => '999',
    'image' => '/cb2/_images/main/flex-black-sleeper-sofa.jpg',
    'LS_ID' => '205,201',
    'viewers' => 4,
  ),
  139 => 
  array (
    'id' => 36988,
    'product_description' => 'Mermelada Estudio\'s versatile design mixes modern materials for an inviting take on the everyday dining table. Brass-finished steel sets a warm base with angled legs that taper to the feet. Paired here with a solid acacia wood top. Part of the mix and match collection, top or base can be swapped out for a whole new look. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Harper Brass Dining Table with Wood Top',
    'product_sku' => '359089',
    'brand_name' => 'CB2',
    'price' => '1248',
    'was_price' => '1299',
    'image' => '/cb2/_images/main/harper-brass-dining-table-with-wood-top.jpg',
    'LS_ID' => '551',
    'viewers' => 4,
  ),
  140 => 
  array (
    'id' => 51672,
    'product_description' => '"Familiarity reigns, favorites are reimagined with a splash of LA cool and a fashion edge," says Fred Segal of their eclectic approach to furniture design. Chic faux leather in grey covers a molded midcentury-inspired seat supported by sleek angled oak legs. Office chair is elegant and minimal at the same time. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Venice Studio Grey Task/Office Chair',
    'product_sku' => '394097',
    'brand_name' => 'CB2',
    'price' => '399',
    'was_price' => '299',
    'image' => '/cb2/_images/main/venice-studio-grey-task-office-chair.jpg',
    'LS_ID' => '610,510,210',
    'viewers' => 4,
  ),
  141 => 
  array (
    'id' => 98155,
    'product_description' => 'Interpret it as farmhouse forward or mixed-material modern--either way, Lakin expanding dining table strikes a sophisticated balance between wood and metal. A refined frame in repurposed teak is topped with hand-selected boards that are sanded, buffed and waxed to a warm glow. ',
    'product_status' => 'active',
    'product_name' => 'Lakin 81" Recycled Teak Extendable Dining Table',
    'product_sku' => '462000',
    'brand_name' => 'Crate&Barrel',
    'price' => '2199',
    'was_price' => '2199',
    'image' => '/cnb/images/main/lakin-81-recycled-teak-extendable-dining-table.jpg',
    'LS_ID' => '552',
    'viewers' => 4,
  ),
  142 => 
  array (
    'id' => 26989,
    'product_description' => 'Willow\'s more modern lines relax in cottage style, instantly putting family rooms and casual living rooms in a vacation state of mind. Deep cushions and a machine-washable slipcover are tailored in a cotton-blend fabric pre-washed for a softer, lived-in touch. ',
    'product_status' => 'active',
    'product_name' => 'Willow Modern Slipcovered Loveseat',
    'product_sku' => '482029',
    'brand_name' => 'Crate&Barrel',
    'price' => '1799',
    'was_price' => '1799',
    'image' => '/cnb/images/main/willow-modern-slipcovered-loveseat.jpg',
    'LS_ID' => '207',
    'viewers' => 4,
  ),
  143 => 
  array (
    'id' => 25341,
    'product_description' => 'Recycled train tracks find new life in this five-in-one metal bed frame that adjusts to accommodate all bed sizes—from twin to California king.  Made in the USA, the black iron frame is thoughtfully designed with rail end linen protectors, generous side rails and six-leg system with center support for a stable foundation. Legs have glides rather than casters, offering better protection of flooring.',
    'product_status' => 'active',
    'product_name' => 'Adjustable Metal Bed Frame',
    'product_sku' => '490691',
    'brand_name' => 'Crate&Barrel',
    'price' => '99.95',
    'was_price' => '99.95',
    'image' => '/cnb/images/main/adjustable-metal-bed-frame.jpg',
    'LS_ID' => '340',
    'viewers' => 4,
  ),
  144 => 
  array (
    'id' => 52049,
    'product_description' => 'Faux mohair in light grey is framed up in an architecturally inspired dining chair worthy of a Parisian loft. Designed by Caleb Zipperer, arms and legs flow together as one and give base to a super plush seat deep enough to pass as a comfy accent chair. Learn about Caleb Zipperer on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Foley Faux Mohair Grey Dining Chair',
    'product_sku' => '591088',
    'brand_name' => 'CB2',
    'price' => '329',
    'was_price' => '329',
    'image' => '/cb2/_images/main/foley-faux-mohair-grey-dining-chair.jpg',
    'LS_ID' => '510,210',
    'viewers' => 4,
  ),
  145 => 
  array (
    'id' => 96789,
    'product_description' => 'Estudiobola knows how to mix materials. Wide balloon base wrapped in buttery leather with stitched detailing and brushed nickel. Topped in natural travertine, dining table makes eating in feel like a special occasion. Learn about estudiobola on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Cruz Travertine Dining Table',
    'product_sku' => '601352',
    'brand_name' => 'CB2',
    'price' => '1997.26',
    'was_price' => '2699',
    'image' => '/cb2/_images/main/cruz-travertine-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 4,
  ),
  146 => 
  array (
    'id' => 97100,
    'product_description' => 'Handcrafted of sustainable solid mango wood, Basque recalls the heft and character of a European farmhouse antique. Hand-planed planks finished with authentic peg detailing are enriched with a honey finish waxed to a soft sheen. ',
    'product_status' => 'active',
    'product_name' => 'Basque Honey 82" Dining Table',
    'product_sku' => '658582',
    'brand_name' => 'Crate&Barrel',
    'price' => '999',
    'was_price' => '999',
    'image' => '/cnb/images/main/basque-honey-82-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 4,
  ),
  147 => 
  array (
    'id' => 57819,
    'product_description' => 'Mermelada Estudio puts a slightly \'70s spin on this vintage-inspired sofa, upholstered in pure white performance fabric for a modern-meets-livable take. Wraparound arms add architectural interest, while three loose back cushions keep it comfortable. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Camden White Sofa',
    'product_sku' => '669071',
    'brand_name' => 'CB2',
    'price' => '1899',
    'was_price' => '1899',
    'image' => '/cb2/_images/main/camden-white-sofa.jpg',
    'LS_ID' => '201',
    'viewers' => 4,
  ),
  148 => 
  array (
    'id' => 35293,
    'product_description' => 'Inspired by mid-century design, the Terrace Coffee Table\'s glass top and shelf
appear to hover above a mirrored glass base, bringing a floaty elegance to
living rooms.',
    'product_status' => 'active',
    'product_name' => 'Terrace Coffee Table',
    'product_sku' => 'terrace-coffee-table-h1030',
    'brand_name' => 'West Elm',
    'price' => '319.2',
    'was_price' => '399',
    'image' => '/westelm/westelm_images/terrace-coffee-table-h1030_main.jpg',
    'LS_ID' => '225',
    'viewers' => 4,
  ),
  149 => 
  array (
    'id' => 35650,
    'product_description' => 'Simple, clean-lined walnut lacquer shelves become a showcase for whatever is
placed on them, whether books, photos and artwork, accessories in the bedroom
or toiletries in the bath. Choose from our wide variety of brackets (sold
separately), for a look that is perfect for you.

###### KEY DETAILS

  * Solid poplar wood in Walnut finish.
  * Sold individually.
  * Brackets required to hang shelves (sold separately).
  * Online/catalog only.
  * Made in Thailand.',
    'product_status' => 'active',
    'product_name' => 'Linear Wood Interchangeable Shelves - Walnut',
    'product_sku' => 'linear-wood-shelves-walnut-d7586',
    'brand_name' => 'West Elm',
    'price' => '50-60',
    'was_price' => '50-60',
    'image' => '/westelm/westelm_images/linear-wood-shelves-walnut-d7586_main.jpg',
    'LS_ID' => '231',
    'viewers' => 4,
  ),
  150 => 
  array (
    'id' => 99900,
    'product_description' => 'Add a rustic touch with our Reclaimed Wood Pedestal Dining Table. Pedestals free up leg room, making it easier to pull up an extra chair or two.',
    'product_status' => 'active',
    'product_name' => 'Reclaimed Wood Pedestal Dining Table',
    'product_sku' => 'reclaimed-wood-pedestal-dining-table-h3266',
    'brand_name' => 'West Elm',
    'price' => '1599',
    'was_price' => '1599',
    'image' => '/westelm/westelm_images/reclaimed-wood-pedestal-dining-table-h3266_main.jpg',
    'LS_ID' => '551',
    'viewers' => 4,
  ),
  151 => 
  array (
    'id' => 35783,
    'product_description' => 'Made from reclaimed pine certified to Forest Stewardship Council® standards,
our Emmerson® Dining Table shows off the natural nicks and knots that make
every table subtly unique. Now expandable, it sits up to 8, so there’s no need
to worry about any unexpected dinner guests.

  * 72"-93"w x 39"d x 30"h.
  * Your purchase of this FSC®-certified product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Solid reclaimed pine in a Stone Gray finish.
  * The reclaimed pine comes from a variety of sources, including shipping pallets and packing crates.
  * Each piece tells the story of its previous life, often marked by scratches, color variations, stains, repair patches and natural imperfections that only add to its beauty.
  * Reclaimed pine has a rugged surface texture that will soften with age and use.
  * Table expands via drop-in leaf.
  * Coordinates with west elm\'s Emmerson® Reclaimed Wood Dining Bench (sold separately).
  * This item is special order and ships directly from the vendor.
  * Imported.
  * Online/catalog only.',
    'product_status' => 'active',
    'product_name' => 'Emmerson® Reclaimed Wood Expandable Dining Table - Stone Gray',
    'product_sku' => 'emmerson-reclaimed-wood-expandable-dining-table-h2218',
    'brand_name' => 'West Elm',
    'price' => '959.4',
    'was_price' => '1599',
    'image' => '/westelm/westelm_images/emmerson-reclaimed-wood-expandable-dining-table-h2218_main.jpg',
    'LS_ID' => '552',
    'viewers' => 4,
  ),
  152 => 
  array (
    'id' => 26549,
    'product_description' => 'When we designed our District Furniture Collection, we thought back to the classic storage furniture we had in elementary school. Then we put a modern twist on the design, giving it a cleaner, more contemporary look. The 3 cube storage frame works with two doors that slide across three spacious storage areas. Choose from two finishes, as well as a variety of different options like bin fronts and doors. Stack up to three units on top of one another for a complete, customized look.Looking for new ways to store kids toys? Check out some of our favorite ideas.',
    'product_status' => 'active',
    'product_name' => 'District 3-Cube Wood Stackable Bookcase',
    'product_sku' => '102942',
    'brand_name' => 'Crate&Barrel',
    'price' => '229',
    'was_price' => '229',
    'image' => '/cnb/images/main/district-3-cube-wood-stackable-bookcase.jpg',
    'LS_ID' => '931,99',
    'viewers' => 3,
  ),
  153 => 
  array (
    'id' => 25920,
    'product_description' => 'Exposed butterfly joints unite planks of solid, live-edge acacia wood in a rustic, organic bench that freshens the living room or entryway. The Parsons-style entryway bench is given a grey tint to call out the wood\'s unique graining, knots and fissures, while the black iron storage shelf offers sleek contrast.  ',
    'product_status' => 'active',
    'product_name' => 'Yukon Grey Entryway Bench with Shelf',
    'product_sku' => '120864',
    'brand_name' => 'Crate&Barrel',
    'price' => '799',
    'was_price' => '799',
    'image' => '/cnb/images/main/yukon-grey-entryway-bench-with-shelf.jpg',
    'LS_ID' => '410,99',
    'viewers' => 3,
  ),
  154 => 
  array (
    'id' => 26378,
    'product_description' => 'When it comes to turning heads, the Avery Swivel is the perfect showstopper for any room. The beautiful dusty mauve velvet fabric is rich and luxurious, while the unique channeled back is perfect for storytime, lounging and more.',
    'product_status' => 'active',
    'product_name' => 'Avery Dusty Mauve Velvet Swivel Chair',
    'product_sku' => '139388',
    'brand_name' => 'Crate&Barrel',
    'price' => '499',
    'was_price' => '499',
    'image' => '/cnb/images/main/avery-dusty-mauve-velvet-swivel-chair.jpg',
    'LS_ID' => '911,99',
    'viewers' => 3,
  ),
  155 => 
  array (
    'id' => 51900,
    'product_description' => 'skinny genes. Hardworking console table is welded heavy-duty but looks light on its feet. Industrial iron with raw antiqued finish. mill console table is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Mill Console Table',
    'product_sku' => '172901',
    'brand_name' => 'CB2',
    'price' => '299',
    'was_price' => '299',
    'image' => '/cb2/_images/main/mill-console-table.jpg',
    'LS_ID' => '227,420',
    'viewers' => 3,
  ),
  156 => 
  array (
    'id' => 97104,
    'product_description' => 'Evoking the elegance of 1940s-era design, the Colette three-drawer chest graces the bedroom with a curved bow front and saber legs in a restful grey-brown stain. Trim molding and classic domed knobs in brushed pewter add extra polish. ',
    'product_status' => 'active',
    'product_name' => 'Colette Driftwood 3-Drawer Chest',
    'product_sku' => '174195',
    'brand_name' => 'Crate&Barrel',
    'price' => '799',
    'was_price' => '799',
    'image' => '/cnb/images/main/colette-driftwood-3-drawer-chest.jpg',
    'LS_ID' => '333,430,99',
    'viewers' => 3,
  ),
  157 => 
  array (
    'id' => 51879,
    'product_description' => 'Three cast aluminum tables nestle in or drift out to create an organic resting place for drinks, books and greens. Each piece surfaces its own unique finishes and patterns, all done by hand. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Lilly 3-Piece Nesting Table Set',
    'product_sku' => '226266',
    'brand_name' => 'CB2',
    'price' => '1299',
    'was_price' => '1199',
    'image' => '/cb2/_images/main/lilly-3-piece-nesting-table-set.jpg',
    'LS_ID' => '225,226',
    'viewers' => 3,
  ),
  158 => 
  array (
    'id' => 51744,
    'product_description' => 'learn the ropes. Tribal bench inspired by traditional Indian cots greens the room in natural materials. Jute rope warps/wefts varying tones over open frame handcrafted of solid sustainable acacia wood. Frame flows continuous to base via mortise and tenon joinery on rounded legs that taper to a pencil-fine point. Iron crossbrace at each end nods to modern industry. Learn about Ayush Kasliwal on our blog. wrap large bench is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Wrap Bench',
    'product_sku' => '274740',
    'brand_name' => 'CB2',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cb2/_images/main/wrap-bench.jpg',
    'LS_ID' => '410',
    'viewers' => 3,
  ),
  159 => 
  array (
    'id' => 27780,
    'product_description' => 'Hayes\' open fretwork of angled brass tubing finds its perfect complement in a round tabletop crafted of solid acacia planks that are expertly joined and shaped to showcase the beautiful grain. This dining table\'s mixed materials expand upon the trend with an elevated design that feels fresh and modern in any setting. The Hayes 48" Round Acacia Dining Table is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Hayes 48" Round Acacia Dining Table',
    'product_sku' => '314111',
    'brand_name' => 'Crate&Barrel',
    'price' => '1099',
    'was_price' => '1099',
    'image' => '/cnb/images/main/hayes-48-round-acacia-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 3,
  ),
  160 => 
  array (
    'id' => 36975,
    'product_description' => 'Total showstopper. Caleb Zipperer\'s dining table design pairs super sleek black marino marble with a vintage-inspired base and brass detailing. We can\'t (and won\'t) stop swooning. Learn more about Caleb Zipperer on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Rocco Rectangular Marble Dining Table',
    'product_sku' => '317304',
    'brand_name' => 'CB2',
    'price' => '2299',
    'was_price' => '2299',
    'image' => '/cb2/_images/main/rocco-rectangular-marble-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 3,
  ),
  161 => 
  array (
    'id' => 51929,
    'product_description' => 'blue room. Clean lines in glossy navy lacquer span almost five feet to broaden storage options. Low-profile frame with expansive top can even pedestal a widescreen. Two clean-front doors hide two adjustable A/V-ready shelves (one on each side) with a gap and cutouts for cord management. Engineered wood case floats on slim steel "L" feet in brushed nickel-plated finish. fuel navy credenza is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Fuel Navy Credenza',
    'product_sku' => '330333',
    'brand_name' => 'CB2',
    'price' => '466.65',
    'was_price' => '499',
    'image' => '/cb2/_images/main/fuel-navy-credenza.jpg',
    'LS_ID' => '430,534',
    'viewers' => 3,
  ),
  162 => 
  array (
    'id' => 51436,
    'product_description' => 'Sofa scales up on modern upholstered frame made for relaxation. Designed by James Patterson, grey sofa\'s slim, discreet profile is edgy and sleek. Brushed brass finished legs squarely support the generous seat, upholstered in chic stone fabric. Learn more about James Patterson on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Hoxton Stone Sofa',
    'product_sku' => '359894',
    'brand_name' => 'CB2',
    'price' => '1099',
    'was_price' => '1299',
    'image' => '/cb2/_images/main/hoxton-stone-sofa.jpg',
    'LS_ID' => '201',
    'viewers' => 3,
  ),
  163 => 
  array (
    'id' => 51705,
    'product_description' => 'ring around the patio. Sculptural seat by designer Jannis Ellenberger comes full circle in a fluid swoop of monochromatic black. Iron tube frame flows from arms to back in one continuous ring, which Ellenberger envisions as a symbol of togetherness. Handwoven of weather-resistant resin rattan, breezy back and seat warp/weft an open, airy weave in a comfy scoop for dining or hanging out. Space-friendly design stacks to store out of the way. Learn more about Jannis Ellenberger on our blog.Pick up tips for decorating with black and white on Idea Central.',
    'product_status' => 'active',
    'product_name' => 'Sophia Black Dining Chair',
    'product_sku' => '377458',
    'brand_name' => 'CB2',
    'price' => '129',
    'was_price' => '119',
    'image' => '/cb2/_images/main/sophia-black-dining-chair.jpg',
    'LS_ID' => '510,210',
    'viewers' => 3,
  ),
  164 => 
  array (
    'id' => 37886,
    'product_description' => '"Familiarity reigns, favorites are reimagined with a splash of LA cool and a fashion edge," says Fred Segal of their eclectic approach to furniture design. Chic brown faux leather covers a molded midcentury-inspired seat supported by sleek angled oak legs. Office chair is equally elegant and minimal. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Venice Studio Brown Task/Office Chair',
    'product_sku' => '394107',
    'brand_name' => 'CB2',
    'price' => '399',
    'was_price' => '299',
    'image' => '/cb2/_images/main/venice-studio-brown-task-office-chair.jpg',
    'LS_ID' => '610,510,210',
    'viewers' => 3,
  ),
  165 => 
  array (
    'id' => 27723,
    'product_description' => 'You\'d never know it by its sleek, streamlined design but this square extension table cleverly stores table linens and flatware. A pull-out drawers integrated into one end of the oak veneer top does the job, keeping the look clean and modern. Made of solid poplar with oak veneer, the top is finished in grey with brown undertones to highlight the grain. Down below, a sturdy iron trestle base echoes the grey in charcoal powdercoat. A separate leaf expands Archive\'s seating potential from four to six. The Archive Square Extension Dining Table is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Archive Square Extension Dining Table',
    'product_sku' => '440971',
    'brand_name' => 'Crate&Barrel',
    'price' => '999',
    'was_price' => '999',
    'image' => '/cnb/images/main/archive-square-extension-dining-table.jpg',
    'LS_ID' => '552',
    'viewers' => 3,
  ),
  166 => 
  array (
    'id' => 27726,
    'product_description' => 'Hayes\' open fretwork of angled brass tubing finds its perfect complement in a top crafted of solid acacia planks that are expertly joined and shaped to showcase the beautiful grain. This mixed-material dining table expands upon the trend with an elevated design that feels fresh and modern in any setting. Generously sized, the rectangular table seats up to eight. The Hayes 94" Rectangular Dining Table is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Hayes 94" Rectangular Dining Table',
    'product_sku' => '442183',
    'brand_name' => 'Crate&Barrel',
    'price' => '1599',
    'was_price' => '1599',
    'image' => '/cnb/images/main/hayes-acacia-metal-base-rectangle-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 3,
  ),
  167 => 
  array (
    'id' => 57870,
    'product_description' => 'fine lines. MASHstudios goes against the grain with bedtime storage in acacia veneer. Two drawers glide silently with subtle pulls. Recessed iron legs with brushed nickel finish add industrial gleam to organic form. Learn about MASHstudios on our blog. linear nightstand is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Linear Nightstand',
    'product_sku' => '442973',
    'brand_name' => 'CB2',
    'price' => '329',
    'was_price' => '299',
    'image' => '/cb2/_images/main/linear-nightstand.jpg',
    'LS_ID' => '335',
    'viewers' => 3,
  ),
  168 => 
  array (
    'id' => 25918,
    'product_description' => 'Exposed butterfly joints unite planks of solid, live-edge acacia wood in a rustic, organic collection that freshens the living room or entryway. The undulating curves of the edges are sanded smooth, exposing natural wood tones. ',
    'product_status' => 'active',
    'product_name' => 'Yukon Natural Entryway Bench with Shelf',
    'product_sku' => '450763',
    'brand_name' => 'Crate&Barrel',
    'price' => '799',
    'was_price' => '799',
    'image' => '/cnb/images/main/yukon-natural-entryway-bench-with-shelf.jpg',
    'LS_ID' => '410,99',
    'viewers' => 3,
  ),
  169 => 
  array (
    'id' => 38152,
    'product_description' => 'one sleek piece. Hi-gloss white lacquer desk downsizes for space but ups the style quotient for office, entryway or bedroom. Discreet sleek dual drawers organize desk supplies and papers, keys and mail (perfect entry console), even cosmetics (makes a great vanity, too).  Pair with a modern office chair for a chic and functional space.',
    'product_status' => 'active',
    'product_name' => 'Runway White Lacquer Desk',
    'product_sku' => '452593',
    'brand_name' => 'CB2',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cb2/_images/main/runway-desk.jpg',
    'LS_ID' => '660',
    'viewers' => 3,
  ),
  170 => 
  array (
    'id' => 51423,
    'product_description' => 'Modern, elegant sofa takes its cues from fashion and architecture. "I wanted to create something that was a bit more sculptural," designer Ceci Thompson explains. Upholstered in snow performance fabric, sofa perches on slim brass legs that give it a light, elegant vibe. Faux leather piping is an unexpected and sophisticated detail: "It\'s something you might see on a handbag, but in this case it plays nicely with a contrasting, softly textured upholstery and defines the form of the sofa." Learn more about Ceci Thompson on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Colette White Sofa with Faux Leather Piping',
    'product_sku' => '474718',
    'brand_name' => 'CB2',
    'price' => '1699',
    'was_price' => '1699',
    'image' => '/cb2/_images/main/colette-white-sofa-with-faux-leather-piping.jpg',
    'LS_ID' => '201',
    'viewers' => 3,
  ),
  171 => 
  array (
    'id' => 26903,
    'product_description' => 'Clean-lined and current, our Gather sofa is covered in buttery Italian-made leather. Similar to that used in high-end handbags and apparel, the leather is tanned, buffed and ironed to create a silky surface that\'s supple, rich and luxurious to the touch. The leather sofa strikes the perfect balance between modern style and comfort with a thin deck that puts all the focus on its boxy, extra-plush seat cushions. Supple Italian leather, trim top-stitching and seamless armrests keep the look neat, not fussy. ',
    'product_status' => 'active',
    'product_name' => 'Gather Leather Sofa',
    'product_sku' => '537138',
    'brand_name' => 'Crate&Barrel',
    'price' => '4099',
    'was_price' => '4099',
    'image' => '/cnb/images/main/gather-petite-leather-sofa.jpg',
    'LS_ID' => '201,99',
    'viewers' => 3,
  ),
  172 => 
  array (
    'id' => 51665,
    'product_description' => 'shearling comfort. Authentic Icelandic sheepskin layers natural, touchable texture as a super-soft chair pad. Cushy comfort and modern style sized to pad most CB2 dining chairs, bar stools or benches. Each shearling is unique and sourced following strict directives for environmental preservation and protection. icelandic sheepskin chair pad is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Icelandic Sheepskin Chair Pad',
    'product_sku' => '561761',
    'brand_name' => 'CB2',
    'price' => '59.95',
    'was_price' => '59.95',
    'image' => '/cb2/_images/main/icelandic-sheepskin-chair-pad.jpg',
    'LS_ID' => '513,210,99',
    'viewers' => 3,
  ),
  173 => 
  array (
    'id' => 96791,
    'product_description' => 'A curator\'s dream. Toughened glass tops whitewashed acacia wood for a functional desk that doubles as a display case. Neatly holds files and supplies or collectibles to inspire productivity. Finished modern with sleek, polished stainless steel legs. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Vista Glass-Top Desk',
    'product_sku' => '590283',
    'brand_name' => 'CB2',
    'price' => '599',
    'was_price' => '599',
    'image' => '/cb2/_images/main/vista-glass-top-desk.jpg',
    'LS_ID' => '660',
    'viewers' => 3,
  ),
  174 => 
  array (
    'id' => 52091,
    'product_description' => 'A goop classic, now upholstered in our favorite snow white performance fabric. As Gwyneth Paltrow says: "It\'s a nod to Italian midcentury designs by way of its fluid lines." The undeniably elegant form doesn\'t sacrifice on comfort, either. Enveloped in a softly textured and durable fabric, its soft crescent shape perches on champagne-finish legs for a light and loungey vibe. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Curvo Snow Sofa',
    'product_sku' => '592866',
    'brand_name' => 'CB2',
    'price' => '1699',
    'was_price' => '1899',
    'image' => '/cb2/_images/main/curvo-snow-sofa.jpg',
    'LS_ID' => '201',
    'viewers' => 3,
  ),
  175 => 
  array (
    'id' => 49795,
    'product_description' => 'Nero curves mid-century modernism in a new direction, mixing metal and concrete in a forward-thinking oval design. An elongated oval of concrete tops a sweeping trumpet base of iron finished in matte black. At once industrial and elegant, Nero seats up to four for brunch, dinner and game night. ',
    'product_status' => 'active',
    'product_name' => 'Nero Oval Concrete Dining Table with Matte Black Base',
    'product_sku' => '595788',
    'brand_name' => 'Crate&Barrel',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/cnb/images/main/nero-oval-concrete-dining-table-with-matte-black-base.jpg',
    'LS_ID' => '551',
    'viewers' => 3,
  ),
  176 => 
  array (
    'id' => 52061,
    'product_description' => 'Minimal design meets the luxest of materials in this statement coffee table. A polished round of solid white quartz rests on three acacia wood legs. Active with hi/lo tones, each piece of quartz is unique. A refined bullnose edge gives the coffee table a sophisticated finish.',
    'product_status' => 'active',
    'product_name' => 'Santoro White Quartz Coffee Table',
    'product_sku' => '600799',
    'brand_name' => 'CB2',
    'price' => '1099',
    'was_price' => '1099',
    'image' => '/cb2/_images/main/santoro-white-quartz-coffee-table.jpg',
    'LS_ID' => '225',
    'viewers' => 3,
  ),
  177 => 
  array (
    'id' => 49880,
    'product_description' => 'While we can\'t promise your house will be library quiet, we can say that our cleverly designed, library-inspired cart will help keep your kids\' books stocked and organized on the shelves, just like the real things.',
    'product_status' => 'active',
    'product_name' => 'White Local Branch Library Cart',
    'product_sku' => '620218',
    'brand_name' => 'Crate&Barrel',
    'price' => '199',
    'was_price' => '199',
    'image' => '/cnb/images/main/white-local-branch-library-cart.jpg',
    'LS_ID' => '931,99',
    'viewers' => 3,
  ),
  178 => 
  array (
    'id' => 100019,
    'product_description' => 'Designed in partnership with the Jane Goodall Institute, this play chair features an artfully illustrated palm leaf print against a blue background. The adventurous design is perfect for any budding nature enthusiast, and will add a vibrant graphic pop to the kids room or playroom. With a seat crafted from rubberwood and metal steel tube legs, this comfy printed kids chair is durable enough for hours of playtime, reading and home learning. Mix and match with the rest of our Jane Goodall Play Chairs.',
    'product_status' => 'active',
    'product_name' => 'Printed Play Chair Blue Palm Leaf',
    'product_sku' => '659383',
    'brand_name' => 'Crate&Barrel',
    'price' => '79',
    'was_price' => '79',
    'image' => '/cnb/images/main/printed-play-chair-blue-palm-leaf.jpg',
    'LS_ID' => '911,99',
    'viewers' => 3,
  ),
  179 => 
  array (
    'id' => 36331,
    'product_description' => 'Brought to you in collaboration with Pottery Barn Kids, our GREENGUARD-
certified Sloan Collection is clever storage made from durable materials. Each
piece is made in a Fair Trade Certified™ facility from wood that\'s kiln-dried
for extra strength, complete with child-safe, water-based finishes. This
bookrack puts your child\'s favorite stories on display and within easy reach.

**KEY DETAILS**

  * 29.6"w x 5.9"d x 46.5"h.
  * Solid poplar frame, solid birch legs and engineered wood shelves.
  * All wood is kiln-dried for added durability.
  * Covered in a child-safe, water-based finish in White.
  * 3 fixed shelves display books face forward (approx. 4" deep).
  * Must be secured to the wall using metal brackets (included).
  * Built-in foot levelers provide stability on uneven floors.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * [GREENGUARD Gold Certified.](http://greenguard.org/en/CertificationPrograms/CertificationPrograms_childrenSchools.aspx) Meets or exceeds stringent chemical and VOC emissions standards.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * Made in a [Fair Trade Certified™](https://www.westelm.com/pages/about-west-elm/our-commitments/fair-trade/) facility in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Sloan Bookrack - White',
    'product_sku' => 'sloan-bookrack-h4505',
    'brand_name' => 'West Elm',
    'price' => '199',
    'was_price' => '199',
    'image' => '/westelm/westelm_images/sloan-bookrack-h4505_main.jpg',
    'LS_ID' => '931',
    'viewers' => 3,
  ),
  180 => 
  array (
    'id' => 99895,
    'product_description' => 'Featuring a solid wood, A-frame base, our Jensen Dining Table borrows its
retro design from classic, mid-century forms. Its clear glass top gives it a
light look that pairs well with upholstered chairs.',
    'product_status' => 'active',
    'product_name' => 'Jensen Dining Table',
    'product_sku' => 'jensen-dining-table-h1039',
    'brand_name' => 'West Elm',
    'price' => '599',
    'was_price' => '599',
    'image' => '/westelm/westelm_images/jensen-dining-table-h1039_main.jpg',
    'LS_ID' => '551',
    'viewers' => 3,
  ),
  181 => 
  array (
    'id' => 89083,
    'product_description' => 'With its industrial design, this modern take on the classic window mirror
makes a great addition to walls that livening up.

###### KEY DETAILS

  * Iron frame in a Dark Bronze finish.
  * Mirrored glass.
  * Hangs vertically or horizontally.
  * Mounting hardware included.
  * Sold individually.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Raven Window Mirror',
    'product_sku' => 'raven-window-mirror-d9104',
    'brand_name' => 'West Elm',
    'price' => '299-599',
    'was_price' => '299-599',
    'image' => '/westelm/westelm_images/raven-window-mirror-d9104_main.jpg',
    'LS_ID' => '1110',
    'viewers' => 3,
  ),
  182 => 
  array (
    'id' => 100557,
    'product_description' => 'Round out your living room or sunroom style with our handcrafted Solana
Papasan Double Chair. Made of durable rattan with a natural finish, it brings a tropical-inspired boho feel to your space with a wide bowl-shaped seat you\'ll want to relax in all day long.',
    'product_status' => 'active',
    'product_name' => 'Solana Papasan Double Chair',
    'product_sku' => 'solana-double-chair-h6969',
    'brand_name' => 'West Elm',
    'price' => '559',
    'was_price' => '559',
    'image' => '/westelm/westelm_images/solana-double-chair-h6969_main.jpg',
    'LS_ID' => '217',
    'viewers' => 3,
  ),
  183 => 
  array (
    'id' => 48853,
    'product_description' => 'With a sturdy, rounded base, the Deodat Oval Dining Table frees up space
thanks to its lack of legs, making extra room for more guests.

###### KEY DETAILS

  * 78.75"w x 43.3"d x 29.5"h.
  * Engineered wood in Walnut veneer.
  * Seats up to 6.
  * Pads to protect floors.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Deodat Oval Dining Table',
    'product_sku' => 'deodat-oval-dining-table-h5324',
    'brand_name' => 'West Elm',
    'price' => '1449',
    'was_price' => '1449',
    'image' => '/westelm/westelm_images/deodat-oval-dining-table-h5324_main.jpg',
    'LS_ID' => '551',
    'viewers' => 3,
  ),
  184 => 
  array (
    'id' => 34906,
    'product_description' => 'Harmony is our most comfortable chair ever, thanks to its deep seat, plush
cushions and go-anywhere lumbar and throw pillows. Made for sprawling out or
curling up, its low, clean-lined frame has reinforced joinery and comes in
your choice of fabric.

###### KEY DETAILS

  * 44.5"w x 41"d x 34"h.
  * Hand-built frames with hand-finished upholstery.
  * Engineered hardwood frame with slot & tenon joinery.
  * Engineered wood legs with veneer in a Dark Walnut finish.
  * Seat cushions: Fiber-wrapped high-density polyurethane foam.
  * Back cushions: 50% polyester fiber, 45% duck feather and 5% duck down in down-proof ticking.
  * Cushions are reversible (Astor Velvet excluded).
  * Throw pillows included.
  * Assembled in the USA.
  * [See](http://www.westelm.com/pages/sofa-buying-guide/) our go-to guide on choosing the sofa that\'s right for you.',
    'product_status' => 'active',
    'product_name' => 'Harmony Chair',
    'product_sku' => 'harmony-chair-and-a-half-h2892',
    'brand_name' => 'West Elm',
    'price' => '879.2-1499',
    'was_price' => '879.2-1499',
    'image' => '/westelm/westelm_images/harmony-chair-and-a-half-h2892_main.jpg',
    'LS_ID' => '210',
    'viewers' => 3,
  ),
  185 => 
  array (
    'id' => 36282,
    'product_description' => 'In beautifully variegated solid marble and brushed brass, this mirror makes a
grand entrance, multiplies light and adds drama to any room.

  * 24"w x 1.6"d x 36"h.
  * Solid marble.
  * Metal in an Antique Brass finish.
  * Mirrored glass.
  * Includes snap screw and anchor for wall mounting.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'White Marble & Brass 36" Wall Mirror',
    'product_sku' => 'marble-brass-wall-mirror-h2900',
    'brand_name' => 'West Elm',
    'price' => '359.1',
    'was_price' => '399',
    'image' => '/westelm/westelm_images/marble-brass-wall-mirror-h2900_main.jpg',
    'LS_ID' => '1110',
    'viewers' => 3,
  ),
  186 => 
  array (
    'id' => 35835,
    'product_description' => 'The Frame Dining Bench is an easy pairing to the matching table (sold
separately) or other industrial style tables.

###### KEY DETAILS

  * Acacia veneer over engineered wood in a Caramel finish.
  * All wood is kiln-dried to prevent warping.
  * Iron frame.
  * Legs feature adjustable levelers to adapt to varying floor levels.
  * Coordinates with West Elm\'s Frame Dining Table (sold separately).
  * Top is made in Vietnam; base is made in Taiwan.',
    'product_status' => 'active',
    'product_name' => 'Frame Dining Bench - Caramel',
    'product_sku' => 'frame-dining-bench-caramel-h4921',
    'brand_name' => 'West Elm',
    'price' => '203.49-254.49',
    'was_price' => '399-499',
    'image' => '/westelm/westelm_images/frame-dining-bench-caramel-h4921_main.jpg',
    'LS_ID' => '511',
    'viewers' => 3,
  ),
  187 => 
  array (
    'id' => 46433,
    'product_description' => 'Our Delphine Buffet is a study in contrast—its substantial body seems to float
on slim metal legs, while a white marble top sits pretty against a sleek
feather gray finish. It offers plenty of room to mix drinks and serve food,
plus ample storage to stash away the plates and glasses once the party\'s over.

###### KEY DETAILS

  * 63"w x 18"d x 28"h.
  * Solid marble top.
  * Solid poplar wood frame with a wood veneer.
  * Covered in a water-based finish in Feather Gray.
  * Bottom panel and interior shelves are engineered wood.
  * Metal legs and hardware in a Light Bronze finish.
  * Four drawers open on metal glides.
  * Center cabinet with an adjustable shelf.
  * Legs feature adjustable levelers to adapt to varying floor levels.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Delphine Buffet - Feather Gray',
    'product_sku' => 'delphine-buffet-feather-gray-h4420',
    'brand_name' => 'West Elm',
    'price' => '1599',
    'was_price' => '1599',
    'image' => '/westelm/westelm_images/delphine-buffet-feather-gray-h4420_main.jpg',
    'LS_ID' => '534',
    'viewers' => 3,
  ),
  188 => 
  array (
    'id' => 35396,
    'product_description' => 'Topped with solid marble, this sleek, brass-finished table is the perfect
height to perch a drink or vase. Its slim footprint makes it ideal for small
spaces.

###### KEY DETAILS

  * 7.25"diam. x 21"h.
  * Solid marble top.
  * Due to the natural material, variations in marble veining and color are to be expected.
  * Stainless steel base in an Antique Brass or Bronze finish.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Silhouette Pedestal Drink Table',
    'product_sku' => 'silhouette-pedestal-drink-table-h4557',
    'brand_name' => 'West Elm',
    'price' => '99',
    'was_price' => '99',
    'image' => '/westelm/westelm_images/silhouette-pedestal-drink-table-h4557_main.jpg',
    'LS_ID' => '226',
    'viewers' => 3,
  ),
  189 => 
  array (
    'id' => 35200,
    'product_description' => 'Our Slope Bar & Counter Stools curve in both the seat and back for extra
comfort. Available in your choice of leather with a timeless metal frame, this
stool is built to last, meeting commercial needs in addition to residential.',
    'product_status' => 'active',
    'product_name' => 'Slope Leather Bar & Counter Stools',
    'product_sku' => 'slope-leather-bar-counter-stools-h1752',
    'brand_name' => 'West Elm',
    'price' => '299-798',
    'was_price' => '299-798',
    'image' => '/westelm/westelm_images/slope-leather-bar-counter-stools-h1752_main.jpg',
    'LS_ID' => '512',
    'viewers' => 3,
  ),
  190 => 
  array (
    'id' => 35700,
    'product_description' => 'We collaborated with Fishs Eddy, the iconic New York City home store, to bring
you their first-ever furniture collection—for the dining room, naturally. This
expandable dining table—with its laminate tabletop that has a retro, textured
look but a smooth feel—offers sharp mid-century style.

  * Created in collaboration with Fishs Eddy. [Learn more](http://www.westelm.com/shop/collaborations/fishs-eddy).
  * 42"-60"w x 42"d x 30"h.
  * Walnut veneer over solid and engineered wood in a Walnut finish.
  * Laminate tabletop.
  * Table expands via drop-in leaf.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Fishs Eddy Expandable Dining Table',
    'product_sku' => 'fishs-eddy-expandable-dining-table-h2403',
    'brand_name' => 'West Elm',
    'price' => '599',
    'was_price' => '599',
    'image' => '/westelm/westelm_images/fishs-eddy-expandable-dining-table-h2403_main.jpg',
    'LS_ID' => '552',
    'viewers' => 3,
  ),
  191 => 
  array (
    'id' => 35709,
    'product_description' => 'Our updated take on the classic show wood frame, our Framework Dining Chair
gets a modern edge—and plenty of intrigue—with a double T back detail. Its
sleek lines and airy feel make it a versatile piece for the dining room or
home office.',
    'product_status' => 'active',
    'product_name' => 'Framework Upholstered Dining Chair',
    'product_sku' => 'framework-upholstered-dining-chair-h2408',
    'brand_name' => 'West Elm',
    'price' => '139.97-279',
    'was_price' => '239-279',
    'image' => '/westelm/westelm_images/framework-upholstered-dining-chair-h2408_main.jpg',
    'LS_ID' => '510',
    'viewers' => 3,
  ),
  192 => 
  array (
    'id' => 35838,
    'product_description' => 'The Frame Expandable Table is a dining essential, providing a spot for
everyday meals, holiday gatherings or even game nights. Its sturdy iron frame
is topped with an expandable wood top that seats up to ten.

###### KEY DETAILS

  * 60–80"w x 36.1"d x 29.6"h.
  * Walnut veneer over an engineered wood top in a Walnut finish.
  * All wood is kiln-dried to prevent warping.
  * Iron frame in an Antique Bronze finish.
  * Expands via drop-in leaf (included).  

  * Legs feature adjustable levelers to adapt to varying floor levels.
  * Coordinates with West Elm\'s Frame Dining Bench (sold separately).
  * Top is made in Vietnam; base is made in Taiwan.',
    'product_status' => 'active',
    'product_name' => 'Frame Expandable Dining Table - Walnut',
    'product_sku' => 'frame-expandable-dining-table-walnut-h4914',
    'brand_name' => 'West Elm',
    'price' => '999-1099',
    'was_price' => '999-1099',
    'image' => '/westelm/westelm_images/frame-expandable-dining-table-walnut-h4914_main.jpg',
    'LS_ID' => '552',
    'viewers' => 3,
  ),
  193 => 
  array (
    'id' => 99903,
    'product_description' => 'Made from reclaimed pine certified to Forest Stewardship Council® (FSC)
standards, our Emmerson® Dining Table shows the knots and natural
imperfections that make each piece subtly one of a kind.',
    'product_status' => 'active',
    'product_name' => 'Emmerson® Reclaimed Wood Dining Table - Ink Black',
    'product_sku' => 'emmerson-reclaimed-wood-dining-table-72-ink-black-h3246',
    'brand_name' => 'West Elm',
    'price' => '1199',
    'was_price' => '1199',
    'image' => '/westelm/westelm_images/emmerson-reclaimed-wood-dining-table-72-ink-black-h3246_main.jpg',
    'LS_ID' => '551',
    'viewers' => 3,
  ),
  194 => 
  array (
    'id' => 51488,
    'product_description' => 'Parisian lines. Inspired by a French flea market find, this sofa\'s long slim lines, cutback retro arms, low tufted back and generous seat cushions rest on modern brass stiletto legs for an unexpected design twist.',
    'product_status' => 'active',
    'product_name' => 'Avec Emerald Velvet Apartment Sofa with Brushed Stainless Steel Legs',
    'product_sku' => '105882',
    'brand_name' => 'CB2',
    'price' => '1199',
    'was_price' => '1499',
    'image' => '/cb2/_images/main/avec-emerald-velvet-apartment-sofa-with-brushed-stainless-steel-legs.jpg',
    'LS_ID' => '201',
    'viewers' => 2,
  ),
  195 => 
  array (
    'id' => 27746,
    'product_description' => 'Sophisticated materials engage in modern geometry to shape this elegant dining table. Brass-finished iron curves the open pedestal base that supports a round top of polished white marble.  At home in the modern or classic dining room, the Damen table seats six.',
    'product_status' => 'active',
    'product_name' => 'Damen 60" White Marble Top Dining Table',
    'product_sku' => '127432',
    'brand_name' => 'Crate&Barrel',
    'price' => '1799',
    'was_price' => '1799',
    'image' => '/cnb/images/main/damen-60-white-marble-top-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 2,
  ),
  196 => 
  array (
    'id' => 97271,
    'product_description' => 'designer lounge. Classic yet surprising, minimalist yet intricate, Filaki elevates any outdoor space. Designed by Jannis Ellenberger, the natural beauty of mahogany wood accents interesting design details like a slatted base and angled legs. Contrasting linen-like cushion in weather-resistant polyester fits any outdoor decor. Best of all, cushion cover zips right off for easy cleaning. Learn more about Jannis Ellenberger on our blog. filaki lounger with natural cushion is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Filaki Lounger with Natural Cushion',
    'product_sku' => '151115',
    'brand_name' => 'CB2',
    'price' => '849',
    'was_price' => '829',
    'image' => '/cb2/_images/main/filaki-lounger-with-natural-cushion.jpg',
    'LS_ID' => '210',
    'viewers' => 2,
  ),
  197 => 
  array (
    'id' => 36999,
    'product_description' => 'Dressed for dinner.  Honed black marble, veined with wisps of white, tops a ribbed iron base in warm brass. Marble table top is trimmed with a flush metal ring in the same warm brass. A design detail we love. Dresses way up with formal dining chairs, goes more casual with contrasting leather/metal seating, or stands stunning alone to welcome visitors in the entry. Seats six easy. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Cypher Black Marble Dining Table',
    'product_sku' => '171430',
    'brand_name' => 'CB2',
    'price' => '1999',
    'was_price' => '1899',
    'image' => '/cb2/_images/main/cypher-black-marble-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 2,
  ),
  198 => 
  array (
    'id' => 51928,
    'product_description' => 'horizontal hold. Clean lines in slicked bright white lacquer span almost five feet to broaden storage options. Expansive top can even pedestal a widescreen. Two doors sans hardware hide two adjustable A/V-ready shelves (one each side) with gap/cutouts for cord management. Wood composite; floats on slim steel "L" feet brushed subtle in nickel plated finish. fuel white credenza is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Fuel White Credenza',
    'product_sku' => '190228',
    'brand_name' => 'CB2',
    'price' => '466.65',
    'was_price' => '499',
    'image' => '/cb2/_images/main/fuel-white-credenza.jpg',
    'LS_ID' => '430,534',
    'viewers' => 2,
  ),
  199 => 
  array (
    'id' => 38163,
    'product_description' => 'height of white. Minimalism scales to the max in clean, pristine white. Sleek desktop surface and three shelves ladder sky high (a CB2 record high at 8 feet) in engineered wood with glossy lacquer. Slick powdercoated aluminum frame with hidden hardware accentuates spotless rise of white. Mounts sturdy to the wall. Create a vertical library with stairway white wall-mounted bookcase. Coordinate with a modern office chair for a stylish and functional space.',
    'product_status' => 'active',
    'product_name' => 'Stairway White 96" Desk',
    'product_sku' => '196072',
    'brand_name' => 'CB2',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cb2/_images/main/stairway-white-96-desk.jpg',
    'LS_ID' => '660',
    'viewers' => 2,
  ),
  200 => 
  array (
    'id' => 26555,
    'product_description' => 'For our next trick, we\'re going to make your bookshelf disappear. Well, not really, but we will allow you to replace it with this modern, seamless bookcart. The clear, acrylic construction makes your books appear as if they\'re floating. Plus, the rolling casters make it easy to move from one corner of the room to the other.Learn our six simple steps to designing a nursery',
    'product_status' => 'active',
    'product_name' => 'Acrylic Book Cart',
    'product_sku' => '250589',
    'brand_name' => 'Crate&Barrel',
    'price' => '299',
    'was_price' => '299',
    'image' => '/cnb/images/main/acrylic-book-cart.jpg',
    'LS_ID' => '931,99',
    'viewers' => 2,
  ),
  201 => 
  array (
    'id' => 25949,
    'product_description' => 'Spotlight is our classic campaign desk, with a contemporary, refined turn. The desk\'s crossed legs—stained deep black—support a sleek tabletop with a slim, wide drawer that slides out to reveal smart storage and flips into a work surface. ',
    'product_status' => 'active',
    'product_name' => 'Spotlight Ebony X-Leg Desk 58"',
    'product_sku' => '256385',
    'brand_name' => 'Crate&Barrel',
    'price' => '599',
    'was_price' => '599',
    'image' => '/cnb/images/main/spotlight-ebony-x-leg-desk-58.jpg',
    'LS_ID' => '660',
    'viewers' => 2,
  ),
  202 => 
  array (
    'id' => 57876,
    'product_description' => 'a fine mesh. Industrial mesh cubbies rise single file, two tiers tall, on handcrafted metallic iron frame with exposed brass welding. Shelves shoes, books and wine in the bedroom, office, kitchen or entryway. We like to grid the wall with multiples to maximize small spaces.For big decorating ideas in small bathrooms, head to Idea Central. sift gold 2-story tower is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Sift Gold 2-Story Tower',
    'product_sku' => '262559',
    'brand_name' => 'CB2',
    'price' => '89.95',
    'was_price' => '89.95',
    'image' => '/cb2/_images/main/sift-gold-2-story-tower.jpg',
    'LS_ID' => '231,335',
    'viewers' => 2,
  ),
  203 => 
  array (
    'id' => 51653,
    'product_description' => 'giddyup. Iconic Breuer style chrome plated base adds a little bounce to comfy one-piece saddle suited up in smart salt/pepper tweed. pony tweed chair is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Pony Tweed Chair',
    'product_sku' => '274773',
    'brand_name' => 'CB2',
    'price' => '149',
    'was_price' => '129',
    'image' => '/cb2/_images/main/pony-tweed-chair.jpg',
    'LS_ID' => '510,210',
    'viewers' => 2,
  ),
  204 => 
  array (
    'id' => 98148,
    'product_description' => 'Our beautiful Yukon table unites planks of solid acacia wood with exposed butterfly joints, bringing live-edge curves and warm wood tones to the casual dining room. Angled, U-shaped legs, fashioned from steel and finished in matte antique black, provide modern counterpoint. ',
    'product_status' => 'active',
    'product_name' => 'Yukon Natural 92" Dining Table',
    'product_sku' => '335376',
    'brand_name' => 'Crate&Barrel',
    'price' => '1999',
    'was_price' => '1999',
    'image' => '/cnb/images/main/yukon-natural-92-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 2,
  ),
  205 => 
  array (
    'id' => 37099,
    'product_description' => 'double feature. This sleeper version of our best-selling Movie Sofa is a two-in-one. The deep down lounge appeal of the sofa we know and love, but with a twin bed inside. It\'s off with the uni-cushion, then pull out the 5.25" coil/foam mattress in one smooth move. Tweedy poly weave lounges lush.',
    'product_status' => 'active',
    'product_name' => 'Movie Twin Sleeper Sofa',
    'product_sku' => '341222',
    'brand_name' => 'CB2',
    'price' => '1399',
    'was_price' => '1399',
    'image' => '/cb2/_images/main/movie-twin-sleeper-sofa.jpg',
    'LS_ID' => '205,201',
    'viewers' => 2,
  ),
  206 => 
  array (
    'id' => 45224,
    'product_description' => 'Help kids get the most out of their teepee with our yellow floor cushion with blue piping. This comfy playtime accessory was designed to fit right into our teepees, giving little ones a soft spot to settle into. That means they can kick back in their favorite place for hour upon hour of play, coloring or reading. And with a machine washable cover, this floor cushion is completely kid-friendly. Plus, the bright colors will compliment our stylish designs while adding a cheery decorative touch to playrooms.',
    'product_status' => 'active',
    'product_name' => 'Yellow with Blue Piping Teepee Cushion',
    'product_sku' => '365344',
    'brand_name' => 'Crate&Barrel',
    'price' => '69',
    'was_price' => '69',
    'image' => '/cnb/images/main/yellow-with-blue-piping-cushion.jpg',
    'LS_ID' => '911',
    'viewers' => 2,
  ),
  207 => 
  array (
    'id' => 27731,
    'product_description' => 'Recast for contemporary dining rooms, the Penn dining table mixes up materials for a warm take on the classic tulip table. A gorgeous tabletop of brown-tinted oak veneer balances on a flared pedestal base made of cast iron with a deep black finish that adds industrial edge.  Round table seats six.',
    'product_status' => 'active',
    'product_name' => 'Penn Brown Oak 55" Pedestal Base Dining Table',
    'product_sku' => '368840',
    'brand_name' => 'Crate&Barrel',
    'price' => '1999',
    'was_price' => '1999',
    'image' => '/cnb/images/main/powell-tulip-base-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 2,
  ),
  208 => 
  array (
    'id' => 26869,
    'product_description' => 'Channeling 1930s Hollywood, Infiniti takes center stage in the modern living room with glamorous curves. The sofa\'s flowing lines sweep an asymmetrical tight back to complement its graceful kidney-shaped seat. Expertly tailored, the grande-sized sofa features crisp self-welt detailing. The Infiniti Grande Curve Back Sofa is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Infiniti Grande Curve Back Sofa',
    'product_sku' => '385167',
    'brand_name' => 'Crate&Barrel',
    'price' => '1899',
    'was_price' => '1899',
    'image' => '/cnb/images/main/item_432_160_555_0.jpg',
    'LS_ID' => '201',
    'viewers' => 2,
  ),
  209 => 
  array (
    'id' => 27730,
    'product_description' => 'Our 1960s-inspired Tate extension dining table recalls the timelessness of mid-century design with a tailored profile and streamlined shape. Featuring beautiful oak and hardwood veneer finished in light sand, the table epitomizes the clean lines of modern design with a streamlined beveled top. Integrated with the table\'s slim apron, conical legs taper down at a subtle angle. Ready to respond for extra dinner guests, the table includes two self-storing leaves that expand seating to 10. Designed by Blake Tovin, the Tate Sand Extendable Midcentury Dining Table is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Tate Sand Extendable Midcentury Dining Table',
    'product_sku' => '388623',
    'brand_name' => 'Crate&Barrel',
    'price' => '1099.99',
    'was_price' => '1199',
    'image' => '/cnb/images/main/tate-sand-extendable-midcentury-dining-table.jpg',
    'LS_ID' => '552',
    'viewers' => 2,
  ),
  210 => 
  array (
    'id' => 51485,
    'product_description' => 'Parisian lines. Inspired by a French flea market find, this sofa\'s long slim lines, cutback retro arms, low tufted back and generous seat cushions rest on modern brass stiletto legs for an unexpected design twist.For fresh ways to make a Victorian home feel modern, head to Idea Central.',
    'product_status' => 'active',
    'product_name' => 'Avec Emerald Green Sofa with Brass Legs',
    'product_sku' => '413594',
    'brand_name' => 'CB2',
    'price' => '1279',
    'was_price' => '1599',
    'image' => '/cb2/_images/main/avec-sofa-with-brass-legs.jpg',
    'LS_ID' => '201',
    'viewers' => 2,
  ),
  211 => 
  array (
    'id' => 51899,
    'product_description' => 'the straight and narrow. Sleek span of silver powdercoated iron slicks up the entry, backs the sofa, or even subs as a bar or cocktail buffet. Compact construction lives comfortably in small spaces.To learn how to give your bathroom the spa treatment, head to Idea Central. mill mini console table is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Mill Mini Console Table',
    'product_sku' => '437774',
    'brand_name' => 'CB2',
    'price' => '199',
    'was_price' => '199',
    'image' => '/cb2/_images/main/mill-mini-console-table.jpg',
    'LS_ID' => '227',
    'viewers' => 2,
  ),
  212 => 
  array (
    'id' => 36964,
    'product_description' => 'Active taupe and black tones swirl and streak across this solid marble top in a gorgeous display of natural beauty. Designed by Brett Beldock, substantial tabletop curves underneath to rest solidly on a mixed-material base of marble and cast aluminum. Seats four beautifully. Learn more about Brett Beldock on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Vex Marble Table',
    'product_sku' => '469632',
    'brand_name' => 'CB2',
    'price' => '2499',
    'was_price' => '2299',
    'image' => '/cb2/_images/main/vex-marble-table.jpg',
    'LS_ID' => '551',
    'viewers' => 2,
  ),
  213 => 
  array (
    'id' => 51792,
    'product_description' => 'knit one, purl two. Accent round layers on sweater in chunky hand-knit graphite. Dense pellet fill is substantial for seat/ottoman duty. 100% cotton cover. knitted graphite pouf is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Knitted Graphite Pouf',
    'product_sku' => '476681',
    'brand_name' => 'CB2',
    'price' => '89.95',
    'was_price' => '79.95',
    'image' => '/cb2/_images/main/knitted-graphite-pouf.jpg',
    'LS_ID' => '222',
    'viewers' => 2,
  ),
  214 => 
  array (
    'id' => 26358,
    'product_description' => 'With a unique 3-in-1 design, the Babyletto Lolly Crib can transform from a crib into a daybed into a toddler bed with ease. It features gently contoured corners and an eye-catching two-tone finish. Plus spindles and feet show off the natural grain of New Zealand Pine Wood. Designed for modern nurseries, the crib also includes a toddler rail for effortless conversion into a toddler bed.',
    'product_status' => 'active',
    'product_name' => 'Babyletto Lolly Washed Natural and Black 3-in-1 Convertible Crib',
    'product_sku' => '488095',
    'brand_name' => 'Crate&Barrel',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cnb/images/main/babyletto-lolly-washed-natural-and-black-3-in-1-convertible-crib.jpg',
    'LS_ID' => '942',
    'viewers' => 2,
  ),
  215 => 
  array (
    'id' => 51596,
    'product_description' => 'Relaxed yet romantic. Cozy chair from the goop x CB2 collection is "proof that things don\'t have to be cold and hard to be chic," says Gwyneth Paltrow. "We\'re taking the compromise out of the equation with chic furniture that\'s to be lived in and loved." Lush, texture-rich fabric feels like a true boucle (for a much lower price). Generously scaled with a mod silhouette that feels slightly vintage and totally high-end. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Gwyneth Boucle Chair',
    'product_sku' => '529566',
    'brand_name' => 'CB2',
    'price' => '899',
    'was_price' => '899',
    'image' => '/cb2/_images/main/gwyneth-boucle-chair.jpg',
    'LS_ID' => '210,99',
    'viewers' => 2,
  ),
  216 => 
  array (
    'id' => 25317,
    'product_description' => 'Jenny Lind, known as the Swedish Nightingale, was an opera singer who performed in the 1800s. She was so popular in her day, furniture styles and household items were named in her honor. The newly designed Jenny Lind Nightstand features the iconic wood turnings that make this style so iconic. Expertly crafted from durable materials, it\'s a dresser that\'ll last as long as Jenny Lind\'s legacy.',
    'product_status' => 'active',
    'product_name' => 'Kids Jenny Lind Black Nightstand',
    'product_sku' => '546223',
    'brand_name' => 'Crate&Barrel',
    'price' => '299',
    'was_price' => '299',
    'image' => '/cnb/images/main/kids-jenny-lind-black-nightstand.jpg',
    'LS_ID' => '934,99',
    'viewers' => 2,
  ),
  217 => 
  array (
    'id' => 26114,
    'product_description' => 'Indoor style travels outdoors with our clean, casual Cayman dining table. Made of sustainably sourced acacia wood tinted grey to resemble teak, the trestle base is masterfully crafted with mortise-and-tenon joinery for superb durability. On top, cool concrete sealed for optimal weather resistance exhibits coloring and markings unique to each piece. ',
    'product_status' => 'active',
    'product_name' => 'Cayman Dining Table',
    'product_sku' => '550452',
    'brand_name' => 'Crate&Barrel',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/cnb/images/main/cayman-dining-table.jpg',
    'LS_ID' => '850',
    'viewers' => 2,
  ),
  218 => 
  array (
    'id' => 98737,
    'product_description' => 'Cool operator. Don\'t have a CB2 bed frame? No problem. These headboard adapter plates allow our headboards to be attached to non-CB2 bed frames and connects mismatched frame and headboards of similar sizes.',
    'product_status' => 'active',
    'product_name' => 'Headboard Adapter Plates',
    'product_sku' => '561262',
    'brand_name' => 'CB2',
    'price' => '11.95',
    'was_price' => '11.95',
    'image' => '/cb2/_images/main/headboard-adapter-plates.jpg',
    'LS_ID' => '345',
    'viewers' => 2,
  ),
  219 => 
  array (
    'id' => 49882,
    'product_description' => 'Edgy without the angles, our curvy Rouelle sofa takes an alluring stance. Thick vertical channels flow from arm to back in one arc, embracing a tight seat that sits comfortably upright. The beautifully tailored upholstery dresses the sofa in a lush fabric that straddles the line between formal and casual. Offering the soft hand of chenille, the textile\'s intriguing interplay of linen and cotton creates a contemporary distressed effect with luxurious depth. Sure to become the focal point of your living room, the Rouelle sofa is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Rouelle Sofa',
    'product_sku' => '568372',
    'brand_name' => 'Crate&Barrel',
    'price' => '2199',
    'was_price' => '2199',
    'image' => '/cnb/images/main/rouelle-sofa.jpg',
    'LS_ID' => '201',
    'viewers' => 2,
  ),
  220 => 
  array (
    'id' => 51363,
    'product_description' => 'Designed in collaboration with American Leather, our exclusive Fuller sleeper sofa beckons with the graceful sweep of slope arms and generous seat and back cushions. When guests come to stay, the sofa opens up to a super-comfy queen mattress that stretches out to a full 80 inches in length, up to 5 inches longer than traditional sleeper mattresses. Equipped with the latest technology to ensure a great night\'s sleep, the mattress is topped with a one-inch layer of ThermaGel that provides support and keeps sleepers cool. ',
    'product_status' => 'active',
    'product_name' => 'Fuller Queen Slope Arm Sleeper Sofa',
    'product_sku' => '579264',
    'brand_name' => 'Crate&Barrel',
    'price' => '3599',
    'was_price' => '3599',
    'image' => '/cnb/images/main/fuller-queen-sleeper-sofa.jpg',
    'LS_ID' => '205,201',
    'viewers' => 2,
  ),
  221 => 
  array (
    'id' => 58426,
    'product_description' => 'Crafted of hearty eucalyptus and graywashed for a contemporary, rustic look, our Marciana outdoor dining bench lets you host gorgeous alfresco feasts for family and friends. With a wide-slatted construction and clean-lined U-shaped base, our Marciana bench offers ample space for outdoor dining with an eye toward memorable contemporary design.',
    'product_status' => 'active',
    'product_name' => 'Graywashed Eucalyptus Marciana Outdoor Dining Bench',
    'product_sku' => '582727',
    'brand_name' => 'World Market',
    'price' => '249.99',
    'was_price' => '249.99',
    'image' => '/nw/images/91479_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '815',
    'viewers' => 2,
  ),
  222 => 
  array (
    'id' => 96767,
    'product_description' => 'White-washed mango wood nightstand supports a unique curved silhouette and configuration: one drawer and two doors concealing a shelf inside for extra storage. We love the on-trend channeled detailing and brass knobs, too. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Cameo Curved Nightstand',
    'product_sku' => '590530',
    'brand_name' => 'CB2',
    'price' => '499',
    'was_price' => '499',
    'image' => '/cb2/_images/main/cameo-curved-nightstand.jpg',
    'LS_ID' => '335',
    'viewers' => 2,
  ),
  223 => 
  array (
    'id' => 51707,
    'product_description' => 'modern bistro. The café chair spiffs up in clean new lines and sophisticated new color. Wide uni-slat back and generous seat with comfy waterfall at the knee shape up in modern matte black, top to bottom. Sturdy tubular iron frames stack out of the way when not in use. lucinda black stacking chair is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Lucinda Black Stacking Chair',
    'product_sku' => '591035',
    'brand_name' => 'CB2',
    'price' => '99.95',
    'was_price' => '89.95',
    'image' => '/cb2/_images/main/lucinda-black-stacking-chair.jpg',
    'LS_ID' => '510,210',
    'viewers' => 2,
  ),
  224 => 
  array (
    'id' => 27803,
    'product_description' => 'Our slipper-style dining chair is all dressed for dinner, donning a light grey slipcover that manages to look both casual and elegant. Skimming the armless hardwood frame and generously padded seat, the full-length slipcover has an informal, unconstructed air, minimally detailed by hand with French seams in the front and along the back. We love the slipcovered dining chair\'s graceful silhouette and to-the-floor coverage.',
    'product_status' => 'active',
    'product_name' => 'Addison Pumice Slipcovered Dining Chair',
    'product_sku' => '592523',
    'brand_name' => 'Crate&Barrel',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cnb/images/main/addison-pumice-slipcovered-dining-chair.jpg',
    'LS_ID' => '510',
    'viewers' => 2,
  ),
  225 => 
  array (
    'id' => 97262,
    'product_description' => 'Asymmetrical ridges carve subtle 3D details into credenza designed by Jannis Ellenberger—details that also function as abstract handles. Bright white engineered wood façade with three doors that open to reveal one large and one small stow space. Rests atop angled, tapered legs in a warm walnut finish with exposed black hardware. Fully finished back floats in room with discreet cutouts for cord management. Learn about Jannis Ellenberger on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Alba Small White Lacquer Credenza',
    'product_sku' => '600637',
    'brand_name' => 'CB2',
    'price' => '649',
    'was_price' => '599',
    'image' => '/cb2/_images/main/alba-small-white-lacquer-credenza.jpg',
    'LS_ID' => '430,534',
    'viewers' => 2,
  ),
  226 => 
  array (
    'id' => 49742,
    'product_description' => 'CEO of sophistication, brought to us by Mermelada Estudio. Modern, oversized executive desk in midtone brown rounds out on either side for maximum storage, one shelf per side. Push-to-open doors curve with the frame and are hidden beautifully with fluted repetition. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Reid Oval Desk',
    'product_sku' => '609895',
    'brand_name' => 'CB2',
    'price' => '1499',
    'was_price' => '1299',
    'image' => '/cb2/_images/main/reid-oval-desk.jpg',
    'LS_ID' => '660',
    'viewers' => 2,
  ),
  227 => 
  array (
    'id' => 66033,
    'product_description' => 'Leather chair by Mermelada Estudio recalls classic midcentury modern style with laid-back luxury. Acacia wood frame supports rich, woven ivory leather seat and back—a high-end design that\'s versatile enough for a slew of settings. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Morada Woven Ivory Leather Chair',
    'product_sku' => '621914',
    'brand_name' => 'CB2',
    'price' => '799',
    'was_price' => '799',
    'image' => '/cb2/_images/main/morada-woven-ivory-leather-chair.jpg',
    'LS_ID' => '210',
    'viewers' => 2,
  ),
  228 => 
  array (
    'id' => 58384,
    'product_description' => 'Circular dining table invites conversation with both its shape and striking design, mixing materials in a way only designer Kara Mann can. The base is crafted of solid cast aluminum, while the resin top features a subtle tonal swirl that evokes high-end marble.  "Pitted platinum feels kind of raw and on the edge, but then we mixed it with a marble resin, so there\'s a formality with this earthiness," Kara says. "The resin is just as beautiful as marble without the maintenance, and then you\'ve got this beautiful architectural metal base." CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Pitted Dining Table',
    'product_sku' => '628163',
    'brand_name' => 'CB2',
    'price' => '1699.15',
    'was_price' => '1999',
    'image' => '/cb2/_images/main/pitted-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 2,
  ),
  229 => 
  array (
    'id' => 97070,
    'product_description' => 'In this scaled-down, kid-friendly version of her Willy Pedestal Bistro Table, Leanne Ford distills the classic pedestal profile down to its striking essentials. This white table\'s bold conical base and perfectly simple circular top bring bold geometry to kids\' seating, offering space for up to four little ones to hang out. The Willy Round Play Table pairs charmingly with any of our kids chairs in neutral or bold hues.Star designer and mom Leanne Ford brought her cool, casual style to our new collection for kids (and parents) who love to create, play and make magic together.',
    'product_status' => 'active',
    'product_name' => 'Willy Round Play Table',
    'product_sku' => '630343',
    'brand_name' => 'Crate&Barrel',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cnb/images/main/willy-round-play-table.jpg',
    'LS_ID' => '921',
    'viewers' => 2,
  ),
  230 => 
  array (
    'id' => 58437,
    'product_description' => 'Minimal design meets the luxest of materials in this statement coffee table. A polished round of solid green agate rests on three acacia wood legs, with a refined bullnose edge for a sophisticated finish. Active with dashes of browns and creams, each piece of agate is unique—a rare and special piece considering we only made a few. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Santoro Green Agate Coffee Table',
    'product_sku' => '632517',
    'brand_name' => 'CB2',
    'price' => '1099',
    'was_price' => '999',
    'image' => '/cb2/_images/main/santoro-green-agate-coffee-table.jpg',
    'LS_ID' => '225',
    'viewers' => 2,
  ),
  231 => 
  array (
    'id' => 27775,
    'product_description' => 'You\'d never know it by its sleek, streamlined design but our Archive table cleverly stores table linens and flatware. Pull-out drawers integrated into either end of the oak veneer top do the job, keeping the look clean and modern. Made of solid poplar with oak veneer, the top is finished in grey with brown undertones to highlight the grain. Down below, a sturdy iron trestle base echoes the grey in charcoal powdercoat. A self-storing leaf expands Archive\'s seating potential from 6 to 10. The Archive Extension Storage Dining Table is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Archive Extension Storage Dining Table',
    'product_sku' => '647127',
    'brand_name' => 'Crate&Barrel',
    'price' => '1199',
    'was_price' => '1199',
    'image' => '/cnb/images/main/archive-extension-storage-dining-table.jpg',
    'LS_ID' => '552',
    'viewers' => 2,
  ),
  232 => 
  array (
    'id' => 26936,
    'product_description' => 'Barrett makes modern affordable and carefree. Upright yet comfortable, this versatile sofa is designed to keep its look casual and clean, even in high-traffic family rooms. The smaller-scaled sofa is perfect for apartments or smaller sized living rooms.  The Barrett Track Arm Apartment Sofa is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Barrett Track Arm Apartment Sofa',
    'product_sku' => '657775',
    'brand_name' => 'Crate&Barrel',
    'price' => '1249',
    'was_price' => '1249',
    'image' => '/cnb/images/main/barrett-track-arm-apartment-sofa.jpg',
    'LS_ID' => '201,99',
    'viewers' => 2,
  ),
  233 => 
  array (
    'id' => 27501,
    'product_description' => 'Teca\'s gorgeous recycled teak and iron poses the perfect solution to runaway shoes, sporting equipment and outdoor gear. This clean-lined trunk opens to a spacious interior just waiting for hats, mittens, balls and bats. ',
    'product_status' => 'active',
    'product_name' => 'Teca Storage Trunk-Bench',
    'product_sku' => '661734',
    'brand_name' => 'Crate&Barrel',
    'price' => '599',
    'was_price' => '599',
    'image' => '/cnb/images/main/teca-storage-trunk-bench.jpg',
    'LS_ID' => '216,410',
    'viewers' => 2,
  ),
  234 => 
  array (
    'id' => 28348,
    'product_description' => 'Paired alongside the Blair bed, this cleverly designed nightstand has a streamlined C-shape to allow easy access to the bed\'s storage drawers without having to move any furniture. Defined by its clean lines and mixed material construction of warm walnut veneer with gunmetal grey linear accents, the nightstand features a single drawer that cantilevers over the base frame shelf. ',
    'product_status' => 'active',
    'product_name' => 'Blair Nightstand',
    'product_sku' => '663755',
    'brand_name' => 'Crate&Barrel',
    'price' => '349',
    'was_price' => '349',
    'image' => '/cnb/images/main/blair-nightstand.jpg',
    'LS_ID' => '335',
    'viewers' => 2,
  ),
  235 => 
  array (
    'id' => 25567,
    'product_description' => 'Form and function converge in a handcrafted metal table that exhibits artisan-crafted details. Sleek in design and intricate in detail, bronze-colored Echelon is warmed by golden undertones and is hand-finished to create an etched appearance. ',
    'product_status' => 'active',
    'product_name' => 'Echelon Console Table',
    'product_sku' => '666958',
    'brand_name' => 'Crate&Barrel',
    'price' => '499',
    'was_price' => '499',
    'image' => '/cnb/images/main/echelon-console-table.jpg',
    'LS_ID' => '227,420',
    'viewers' => 2,
  ),
  236 => 
  array (
    'id' => 27709,
    'product_description' => 'Our Revolve adjustable dining table puts a new spin on versatility, adjusting in height with a few turns to accommodate dining chairs, counter stools and bar stools. Perfect for all occasions, from everyday meals to pub-style high dining, this ingenious round pedestal table has an industrial feel, revolving a solid acacia table top around a threaded center metal support that\'s anchored by a substantial acacia wood base. ',
    'product_status' => 'active',
    'product_name' => 'Revolve 48" Round Adjustable Height Dining Table',
    'product_sku' => '672566',
    'brand_name' => 'Crate&Barrel',
    'price' => '1199',
    'was_price' => '1199',
    'image' => '/cnb/images/main/revolve-48-round-adjustable-height-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 2,
  ),
  237 => 
  array (
    'id' => 58324,
    'product_description' => '',
    'product_status' => 'active',
    'product_name' => 'Notch Sofa',
    'product_sku' => '680626',
    'brand_name' => 'Crate&Barrel',
    'price' => '1699',
    'was_price' => '1699',
    'image' => '/cnb/images/main/notch-sofa.jpg',
    'LS_ID' => '201',
    'viewers' => 2,
  ),
  238 => 
  array (
    'id' => 51088,
    'product_description' => 'The cool industrial styling of our dining table is warmed up with beautiful bamboo detail in a rich espresso finish. This appealing modern piece boasts tapered metal legs with antiqued finish for an inviting look you\'ll adore at every meal.',
    'product_status' => 'active',
    'product_name' => 'Antique Metal and Espresso Wood Arwen Dining Table',
    'product_sku' => '10014433',
    'brand_name' => 'World Market',
    'price' => '499.99',
    'was_price' => '499.99',
    'image' => '/nw/images/67275_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '551',
    'viewers' => 2,
  ),
  239 => 
  array (
    'id' => 50183,
    'product_description' => 'Boasting a classic silhouette with turned legs, our rustic dining table is handcrafted in Indonesia of solid teakwood and finished to emphasize its natural beauty.',
    'product_status' => 'active',
    'product_name' => 'Teakwood Theodora Dining Table',
    'product_sku' => '10014635',
    'brand_name' => 'World Market',
    'price' => '899.99',
    'was_price' => '899.99',
    'image' => '/nw/images/68279_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '551',
    'viewers' => 2,
  ),
  240 => 
  array (
    'id' => 35703,
    'product_description' => 'Standing on solid wood legs, this A-frame bench is a versatile piece that
easily works in the dining room or even the entryway.

###### KEY DETAILS

  * 52"w x 15"d x 18"h.
  * Solid wood legs.
  * Solid + engineered wood seat with a Walnut veneer.
  * All wood is kiln-dried to prevent warping.
  * Covered in a Walnut finish.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Mid-Century A-Frame Bench',
    'product_sku' => 'a-frame-bench-h4311',
    'brand_name' => 'West Elm',
    'price' => '299',
    'was_price' => '299',
    'image' => '/westelm/westelm_images/a-frame-bench-h4311_main.jpg',
    'LS_ID' => '511',
    'viewers' => 2,
  ),
  241 => 
  array (
    'id' => 35242,
    'product_description' => 'Our Denmark Sofa\'s slender frame, low-slung profile and tapered legs are
inspired by Danish modern design.

###### KEY DETAILS

  * 66.1"w x 34"d x 31.1"h.
  * Solid wood legs in a Dark Oak finish.
  * Seat firmness: Medium. On a scale from 1 to 5 (5 being firmest), we rate it a 4.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](http://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Imported.
  * [See](http://www.westelm.com/pages/sofa-buying-guide/) our go-to guide on choosing the sofa that\'s right for you.',
    'product_status' => 'active',
    'product_name' => 'Denmark Sofa',
    'product_sku' => 'denmark-loveseat-h2312',
    'brand_name' => 'West Elm',
    'price' => '1099-1499',
    'was_price' => '1099-1499',
    'image' => '/westelm/westelm_images/denmark-loveseat-h2312_main.jpg',
    'LS_ID' => '201',
    'viewers' => 2,
  ),
  242 => 
  array (
    'id' => 35679,
    'product_description' => 'With a sculptural base inspired by Italian mid-century design, our
thoughtfully constructed Wright Nightstand is crafted from sustainably sourced
wood, covered in water-based finishes and Greenguard Gold Certified as being
low VOC.',
    'product_status' => 'active',
    'product_name' => 'Wright Nightstand',
    'product_sku' => 'wright-nightstand-h3704',
    'brand_name' => 'West Elm',
    'price' => '349-698',
    'was_price' => '349-698',
    'image' => '/westelm/westelm_images/wright-nightstand-h3704_main.jpg',
    'LS_ID' => '335',
    'viewers' => 2,
  ),
  243 => 
  array (
    'id' => 35870,
    'product_description' => 'Our Greer Dining Chair has a modern, curved silhouette with a wraparound seat
back for added comfort. Its versatile design makes it easy to pull up next to
any style dining table.

###### KEY DETAILS

  * 20"w x 19"d x 31"h.
  * Hand upholstered seat and back.
  * Hand-welded metal legs.
  * Imported.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](http://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Select options are online/catalog only.',
    'product_status' => 'active',
    'product_name' => 'Greer Upholstered Dining Chair',
    'product_sku' => 'greer-dining-chair-h3066',
    'brand_name' => 'West Elm',
    'price' => '264-309',
    'was_price' => '264-309',
    'image' => '/westelm/westelm_images/greer-dining-chair-h3066_main.jpg',
    'LS_ID' => '510',
    'viewers' => 2,
  ),
  244 => 
  array (
    'id' => 35885,
    'product_description' => 'Our Nailhead Dining Chair mixes the best of traditional and contemporary
design. Its modern wingback shape and canvas upholstery are accented with
classic design details like nailhead trim. Small enough to use at a dining
table or at a desk, this chair is at home in any room of the house.

  * 20"w x 22"d x 33"h.
  * Solid wood legs.
  * 85% cotton, 15% polyester canvas upholstery in Dark Moon.
  * Polyester foam padding for maximum comfort.
  * Nailhead trim.
  * Imported
  * Online/catalog only.',
    'product_status' => 'active',
    'product_name' => 'Nailhead Upholstered Dining Chair',
    'product_sku' => 'nailhead-task-chair-h1579',
    'brand_name' => 'West Elm',
    'price' => '399',
    'was_price' => '399',
    'image' => '/westelm/westelm_images/nailhead-task-chair-h1579_main.jpg',
    'LS_ID' => '510',
    'viewers' => 2,
  ),
  245 => 
  array (
    'id' => 34843,
    'product_description' => 'With its modern form, extra-deep seat and crisp tailoring, our spacious Andes
Sectional has serious presence but feels airy and light thanks to the thin
frame and cast metal legs. Its durable frame is Contract Grade and comes in
your choice of width, depth and leg finish.',
    'product_status' => 'active',
    'product_name' => 'Andes 3-Piece L-Shaped Sectional',
    'product_sku' => 'andes-l-shape-sectional-h1816',
    'brand_name' => 'West Elm',
    'price' => '3497-5197',
    'was_price' => '3497-5197',
    'image' => '/westelm/westelm_images/andes-l-shape-sectional-h1816_main.jpg',
    'LS_ID' => '202',
    'viewers' => 2,
  ),
  246 => 
  array (
    'id' => 36403,
    'product_description' => 'You can almost feel the island breeze while relaxing on our Coastal Outdoor
Sofa. A modern handwoven base holds inviting cushions for tons of laidback
texture in your outdoor space.

###### KEY DETAILS

  * 76"w x 32.75"d x 25.7"h.
  * Handwoven all-weather wicker in Silverstone.
  * Powder-coated aluminum frame.
  * All-weather wicker resists fading, sagging and cracking.
  * Your purchase of handcrafted items helps preserve craft traditions worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/handcrafted/)
  * Weather-resistant olefin cushions in Gray (included).
  * Seat cushion has high-resiliency polyurethane foam cores.
  * Back cushions are poly fiber-filled.
  * Loose, non-reversible cushions with zip-off covers.
  * Includes two bolster pillows.
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Coastal Outdoor Sofa (76")',
    'product_sku' => 'coastal-outdoor-sofa-76-h5288',
    'brand_name' => 'West Elm',
    'price' => '1699',
    'was_price' => '1699',
    'image' => '/westelm/westelm_images/coastal-outdoor-sofa-76-h5288_main.jpg',
    'LS_ID' => '801',
    'viewers' => 2,
  ),
  247 => 
  array (
    'id' => 35810,
    'product_description' => 'Made from richly-grained solid mango wood and lofted on blackened metal legs,
our Industrial Modular Desk features two separate work surfaces and a bottom
shelf to prop your feet up on. The attached box file keeps papers and supplies
organized and out of the way.

  * 64"w x 64"d x 31"h.
  * Solid mango wood; metal frame with a Black finish.
  * Natural variations in the wood grain and coloring are to be expected.
  * Bottom file drawer holds legal and letter sized documents.
  * Box File can be arranged on the left or right.
  * The mango wood used on this product is sustainably sourced from trees that no longer produce fruit. 
  * Made in India.
  * Online/catalog only.',
    'product_status' => 'active',
    'product_name' => 'Industrial Modular Desk Set',
    'product_sku' => 'rustic-modular-desk-set-h1033',
    'brand_name' => 'West Elm',
    'price' => '1399',
    'was_price' => '1399',
    'image' => '/westelm/westelm_images/rustic-modular-desk-set-h1033_main.jpg',
    'LS_ID' => '660',
    'viewers' => 2,
  ),
  248 => 
  array (
    'id' => 35651,
    'product_description' => 'Simple, clean-lined white lacquer shelves become a showcase for whatever is
placed on them, whether books, photos and artwork, accessories in the bedroom
or toiletries in the bath. Choose from our wide variety of brackets (sold
separately), for a look that is perfect for you.

###### KEY DETAILS

  * Shelf: Engineered wood with a White lacquer finish.
  * Prism Brackets: Stainless steel in Antique Bronze or Antique Brass finish.
  * Piston Wall Brackets: Mild steel in Antique Brass finish.
  * Hardware included.
  * Sold individually.
  * Brackets required to hang shelves (sold separately).
  * Online/catalog only.
  * Made in Thailand.',
    'product_status' => 'active',
    'product_name' => 'Linear Lacquer Interchangeable Shelves - White',
    'product_sku' => 'linear-lacquer-shelves-oyster-d7584',
    'brand_name' => 'West Elm',
    'price' => '30-40',
    'was_price' => '30-40',
    'image' => '/westelm/westelm_images/linear-lacquer-shelves-oyster-d7584_main.jpg',
    'LS_ID' => '231',
    'viewers' => 2,
  ),
  249 => 
  array (
    'id' => 35649,
    'product_description' => 'These clean-lined, solid wood floating shelves add the warmth and texture of
natural wood while showcasing whatever is placed on them. They’re a handsome
home for anything from books, photos, art and collections to office or kitchen
supplies. Choose from our wide variety of brackets (sold separately), for a
look that is perfect for you.

###### KEY DETAILS

  * Solid mango wood in Burnt Wax finish.
  * Made in a [Fair Trade Certified™](https://www.westelm.com/pages/about-west-elm/our-commitments/fair-trade/) facility, directly benefiting the workers who make it.
  * Sold individually.
  * Brackets required to hang shelves (sold separately).
  * Online/catalog only.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Linear Wood Interchangeable Shelves - Burnt Wax',
    'product_sku' => 'linear-wood-shelves-burnt-wax-d7585',
    'brand_name' => 'West Elm',
    'price' => '45-60',
    'was_price' => '50-60',
    'image' => '/westelm/westelm_images/linear-wood-shelves-burnt-wax-d7585_main.jpg',
    'LS_ID' => '231',
    'viewers' => 2,
  ),
  250 => 
  array (
    'id' => 34884,
    'product_description' => 'Our best-selling Portside Outdoor Collection gets a modern update with a sleek
aluminum frame. This outdoor sofa brings living room comfort to the backyard
with its roomy profile, rust-proof frame and weather-resistant cushions.

###### KEY DETAILS

  * 72"w x 35.8"d x 32.5"h.
  * Aluminum frame in a powder-coated Antique Bronze finish.
  * Weather-resistant cushions with 100% polyester covers in Gray (included).
  * Seat cushions feature polyurethane foam filling.
  * Back cushions feature polyester-wrapped Dacron + fiber filling.
  * Loose, non-reversible cushions with zip-off covers.
  * Aluminum furniture may get hot in direct sunlight; ours cools quickly in the shade thanks to its construction.
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Portside Aluminum Outdoor Sofa',
    'product_sku' => 'portside-aluminum-outdoor-sofa-h4142',
    'brand_name' => 'West Elm',
    'price' => '1649',
    'was_price' => '1649',
    'image' => '/westelm/westelm_images/portside-aluminum-outdoor-sofa-h4142_main.jpg',
    'LS_ID' => '801',
    'viewers' => 2,
  ),
  251 => 
  array (
    'id' => 99512,
    'product_description' => 'Designed for cafes and restaurants, this scratch-resistant table carves out a dining nook in even the smallest of spaces. Each table base is smartly
equipped with adjustable glides that adapt to varying floor levels—making the dreaded table-wobble a thing of the past. Use them in multiples to create more dining space that can be separated later.',
    'product_status' => 'active',
    'product_name' => 'Dark Walnut Square Bistro Table',
    'product_sku' => 'dark-walnut-square-dining-table-h3439',
    'brand_name' => 'West Elm',
    'price' => '399',
    'was_price' => '399',
    'image' => '/westelm/westelm_images/dark-walnut-square-dining-table-h3439_main.jpg',
    'LS_ID' => '551',
    'viewers' => 2,
  ),
  252 => 
  array (
    'id' => 65976,
    'product_description' => 'Brought to you in collaboration with Pottery Barn Kids, our GREENGUARD-
certified Knox Nursery Collection combines two-tone style with durable
craftsmanship. Each piece is made from sustainably sourced wood that\'s kiln
dried for extra strength, complete with child-safe, water-based finishes. This
crib grows with your baby, featuring two platform height options, and can be
converted into a cozy toddler bed with the matching conversion kit (sold
separately).

###### KEY DETAILS

  * Kiln-dried solid birch and pine wood.
  * Covered in child-safe, water-based lacquer finish in Natural/Black.
  * Metal mattress platform has height options to accommodate your growing baby (mattress sold separately).
  * All wood is sustainably sourced and [FSC®-certified](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/).
  * [GREENGUARD Gold Certified.](http://greenguard.org/en/CertificationPrograms/CertificationPrograms_childrenSchools.aspx) Meets or exceeds stringent chemical and VOC emissions standards.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * Please read Safety Information tab for important crib safety callouts.
  * When purchasing a convertible crib, it is strongly recommended to purchase any desired conversion kits at the same time.
  * Made in a Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Knox Convertible Crib - Natural/Black',
    'product_sku' => 'knox-convertible-crib-natural-black-d7997',
    'brand_name' => 'West Elm',
    'price' => '34.99',
    'was_price' => '49',
    'image' => '/westelm/westelm_images/knox-convertible-crib-natural-black-d7997_main.jpg',
    'LS_ID' => '942',
    'viewers' => 2,
  ),
  253 => 
  array (
    'id' => 35028,
    'product_description' => 'The clean, strong lines of our Henry® Collection make it an instant crowd-
pleaser. This sectional works triple-duty—in addition to its roomy seats and
storage space, we gave it a Full-sized sleeper mattress that\'s perfect for
overnight guests.',
    'product_status' => 'active',
    'product_name' => 'Henry® 2-Piece Full Sleeper Sectional w/ Storage',
    'product_sku' => 'henry-2-piece-sleeper-sectional-storage-h2073',
    'brand_name' => 'West Elm',
    'price' => '2898-3698',
    'was_price' => '2898-3698',
    'image' => '/westelm/westelm_images/henry-2-piece-sleeper-sectional-storage-h2073_main.jpg',
    'LS_ID' => '202',
    'viewers' => 2,
  ),
  254 => 
  array (
    'id' => 36594,
    'product_description' => 'Inspired by mid-century Italian design, our popular Finley Chair now comes as
a versatile office chair with a swivel base. The curved, tapered back and
adjustable seat offer much-needed comfort in a home office but are stylish
enough to naturally blend into a living room or bedroom.

###### KEY DETAILS

  * 26.2"diam. x 32.75"–36"h.
  * Wood frame with a bentwood veneer.
  * Metal legs and swivel base in an Antique Brass finish.
  * Upholstered seat and back.
  * Adjustable seat height.
  * Care should be taken when placing this chair directly on wooden floors; to prevent scratches, use a protective mat.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Finley Swivel Office Chair',
    'product_sku' => 'finley-swivel-office-chair-antique-brass-h5338',
    'brand_name' => 'West Elm',
    'price' => '399-449',
    'was_price' => '399-449',
    'image' => '/westelm/westelm_images/finley-swivel-office-chair-antique-brass-h5338_main.jpg',
    'LS_ID' => '610',
    'viewers' => 2,
  ),
  255 => 
  array (
    'id' => 35379,
    'product_description' => 'For a touch of Art Deco glam, we finished the sculptural metal bases of our
Round Nesting Tables with smooth marble tabletops. Scaled for smaller spaces,
its nesting function lets you hide the smaller table under the larger one, and
pull it out when you need more surface space.

###### KEY DETAILS

  * Marble tops.
  * Natural variations in the color and veining of marble make each piece subtly unique.
  * Metal base.
  * Set of 2 includes one small table and one large table.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](http://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Marble Round Nesting Side Table (Set of 2)',
    'product_sku' => 'round-nesting-side-tables-marble-antique-brass-h2287',
    'brand_name' => 'West Elm',
    'price' => '399',
    'was_price' => '399',
    'image' => '/westelm/westelm_images/round-nesting-side-tables-marble-antique-brass-h2287_main.jpg',
    'LS_ID' => '226',
    'viewers' => 2,
  ),
  256 => 
  array (
    'id' => 49747,
    'product_description' => 'Keeping the same brushed texture you love, we recreated our popular Portside
Collection in weather-proof POLYWOOD®, a blend of recycled plastics that is
built to withstand all four seasons and a range of climates. This sectional
frame is compatible with our Portside Outdoor Cushions (sold separately),
making it easy to create an outdoor relaxation zone that\'s durable and
comfortable.

###### KEY DETAILS

  * 124.5"w x 97"d x 30"h.
  * High-density polyethylene POLYWOOD® frame.
  * Marine-grade quality hardware.
  * POLYWOOD® lumber is a blend of plastics, including recycled milk jugs and detergent bottles.
  * All-weather and built to withstand a range of climates, from hot sun and snowy winters to strong coastal winds.
  * Does not stain, splinter, crack, chip, peel, rot or corrode.
  * Cleans easily with soap, water and a soft-bristle brush.
  * Coordinates with our Portside Outdoor Cushions (sold separately).
  * Assembled in the USA from recycled materials.',
    'product_status' => 'active',
    'product_name' => 'Portside by Polywood 4-Piece L-Shaped Sectional',
    'product_sku' => 'portside-by-polywood-4-piece-l-shaped-sectional-h5922',
    'brand_name' => 'West Elm',
    'price' => '4026',
    'was_price' => '4026',
    'image' => '/westelm/westelm_images/portside-by-polywood-4-piece-l-shaped-sectional-h5922_main.jpg',
    'LS_ID' => '806',
    'viewers' => 2,
  ),
  257 => 
  array (
    'id' => 49577,
    'product_description' => 'We did the pairing for you: Our Zane Wide Bookshelf and matching Narrow
Bookshelves pack plenty of storage space for a living room or study. In sleek
lacquer and brass, each piece elevates the essentials with shelving for
everything from books to baskets to media.

###### KEY DETAILS

  * Set includes: 1 Zane Wide Bookshelf (White) and 2 Zane Narrow Bookshelves (White).
  * For individual product information: [Zane Wide Bookshelf - White, ](https://www.westelm.com/products/zane-wide-bookshelf-white-h3414)[Zane Narrow Bookshelf - White.](https://www.westelm.com/products/zane-narrow-bookshelf-white-h3417)',
    'product_status' => 'active',
    'product_name' => 'Zane Wide Bookshelf & 2 Narrow Bookshelves Set - White',
    'product_sku' => 'zane-wide-bookshelf-2-narrow-bookshelves-set-white-h6181',
    'brand_name' => 'West Elm',
    'price' => '1097',
    'was_price' => '1097',
    'image' => '/westelm/westelm_images/zane-wide-bookshelf-2-narrow-bookshelves-set-white-h6181_main.jpg',
    'LS_ID' => '231',
    'viewers' => 2,
  ),
  258 => 
  array (
    'id' => 89072,
    'product_description' => 'With ends that seat two and a drop-in leaf that makes room for two more, our
Extra Deep Mid-Century Expandable Dining Table is seven inches deeper than our
standard version, seating up to 12. Its sturdy frame is crafted from
sustainably sourced wood and made in a Fair Trade Certified™ facility.',
    'product_status' => 'active',
    'product_name' => 'Extra Deep Mid-Century Expandable Dining Table - Walnut',
    'product_sku' => 'extra-deep-mid-century-expandable-dining-table-walnut-h6599',
    'brand_name' => 'West Elm',
    'price' => '1199',
    'was_price' => '1199',
    'image' => '/westelm/westelm_images/extra-deep-mid-century-expandable-dining-table-walnut-h6599_main.jpg',
    'LS_ID' => '552',
    'viewers' => 2,
  ),
  259 => 
  array (
    'id' => 37025,
    'product_description' => ' hi-yo silver.  No lone rangers here. Two sleek chrome modern "sawhorses" partner with crisp, clean clear glass tops, rectangle or round—your choice. For more leg room and stability, turn the chrome-plated iron structures T-in at the ends (shown). silverado chrome 72" rectangular dining table is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Silverado Chrome 72" Rectangular Dining Table',
    'product_sku' => '116307',
    'brand_name' => 'CB2',
    'price' => '349',
    'was_price' => '349',
    'image' => '/cb2/_images/main/silverado-rectangular-dining-table.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  260 => 
  array (
    'id' => 37010,
    'product_description' => 'a leg up. Plantation grown solid mango wood planks shift perspective in asymmetric wide/narrow leg rotation. Bird\'s eye view from the top is stunning: sweeping woodgrain in midtone brown, inset legs create directional geometry (and dinner conversation). XL span seats party of ten. blox 35x91 dining table is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Blox 35x91 Dining Table',
    'product_sku' => '123851',
    'brand_name' => 'CB2',
    'price' => '999',
    'was_price' => '999',
    'image' => '/cb2/_images/main/blox-35x91-dining-table.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  261 => 
  array (
    'id' => 37053,
    'product_description' => 'patio café. Polished stainless steel slicks up the classic bistro table in this modern interpretation by Mark Daniel of Slate Design. Elevated on a sleek pedestal frame, finely textured top echoes the round shape of the smooth base. Its compact design makes the most of small outdoor spaces, scaled to comfortably dine two on the patio or urban balconies. Learn about Mark Daniel on our blog. watermark bistro table is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Watermark Bistro Table',
    'product_sku' => '125988',
    'brand_name' => 'CB2',
    'price' => '279',
    'was_price' => '249',
    'image' => '/cb2/_images/main/watermark-bistro-table.jpg',
    'LS_ID' => '551,850',
    'viewers' => 1,
  ),
  262 => 
  array (
    'id' => 97615,
    'product_description' => 'Natural materials take a cue from modern geometry. Gunmetal-finished iron forms uniquely curved angles to balance a contrasting brown tabletop of rich reclaimed woods. A refined spin on trend-forward design, the round table seats four.',
    'product_status' => 'active',
    'product_name' => 'Damen 48" Brown Wood Top Dining Table',
    'product_sku' => '128297',
    'brand_name' => 'Crate&Barrel',
    'price' => '1099',
    'was_price' => '1099',
    'image' => '/cnb/images/main/damen-48-brown-wood-top-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 1,
  ),
  263 => 
  array (
    'id' => 28379,
    'product_description' => 'Finch offers intriguing contrasts in a simple silhouette. The nightstand\'s hammered gunmetal base takes a stool-like stance, topped with a smooth round of white marble.',
    'product_status' => 'active',
    'product_name' => 'Finch Nightstand',
    'product_sku' => '128571',
    'brand_name' => 'Crate&Barrel',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cnb/images/main/finch-nightstand.jpg',
    'LS_ID' => '335',
    'viewers' => 1,
  ),
  264 => 
  array (
    'id' => 58350,
    'product_description' => 'Wild side. Divine ivory shagreen is boudoir-level luxury on a minimal modern frame. Designed by Amanda Ip of Slate Design, striking nightstand is completely wrapped in faux embossed sharkskin-like shagreen, by hand no less. Braced by a slim, open structure brass frame, silhouette is the picture of chic sophistication. Two deep drawers slide effortlessly on metal glides with brass-finished knobs. Learn about Amanda Ip on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Ivory Shagreen Embossed Nightstand',
    'product_sku' => '129706',
    'brand_name' => 'CB2',
    'price' => '449',
    'was_price' => '449',
    'image' => '/cb2/_images/main/ivory-shagreen-embossed-nightstand.jpg',
    'LS_ID' => '335',
    'viewers' => 1,
  ),
  265 => 
  array (
    'id' => 27787,
    'product_description' => 'Elegant marble-top island translates French style into a timeless design that evokes the charm of an Old World pastry shop. Practical and beautiful, the white marble top sits on a classically inspired metal base. ',
    'product_status' => 'active',
    'product_name' => 'French Kitchen Island',
    'product_sku' => '131558',
    'brand_name' => 'Crate&Barrel',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/cnb/images/main/french-kitchen-island.jpg',
    'LS_ID' => '535',
    'viewers' => 1,
  ),
  266 => 
  array (
    'id' => 57991,
    'product_description' => 'Classic lines.Retro lives modern in this wire cage side table design from the bright minds at Berlin-based design studio Hettler.Tüllmann. Inspired by \'50s and \'60s wire rod furniture, open iron frame powdercoated matte black looks sleek on the patio to perch drinks and magazines. Chic in combo with Black Wire Coffee Table. Learn about Hettler.Tüllmann on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Black Wire Side Table',
    'product_sku' => '143886',
    'brand_name' => 'CB2',
    'price' => '169',
    'was_price' => '169',
    'image' => '/cb2/_images/main/black-wire-side-table.jpg',
    'LS_ID' => '226,826,89',
    'viewers' => 1,
  ),
  267 => 
  array (
    'id' => 51650,
    'product_description' => 'live wire. Antiqued powdercoated iron ebbs and flows an undulating grid of surprisingly comfortable contours. Handcrafted to graphic extreme, industrial frame spans a generous seat and back that subtly curve in a single contoured swoop. Showing the mark of a metalsmith with exposed brass handwelding at each crosspoint, chair lounges low and deep with a fluid waterfall at the knee. Sits squarely on sleigh legs with stability bar. Up the cush factor with agency chair cushion. agency chair is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Agency Chair',
    'product_sku' => '200600',
    'brand_name' => 'CB2',
    'price' => '349',
    'was_price' => '349',
    'image' => '/cb2/_images/main/agency-chair.jpg',
    'LS_ID' => '210',
    'viewers' => 1,
  ),
  268 => 
  array (
    'id' => 57877,
    'product_description' => 'a fine mesh. Industrial mesh cubbies rise single file, two tiers tall, on handcrafted iron frame with exposed brass welding. Shelves shoes, books and wine in the bedroom, office, kitchen or entryway. We like to grid the wall with multiples to maximize small spaces. sift gunmetal 2-story tower is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Sift Gunmetal 2-Story Tower',
    'product_sku' => '202946',
    'brand_name' => 'CB2',
    'price' => '89.95',
    'was_price' => '89.95',
    'image' => '/cb2/_images/main/sift-2-story-tower.jpg',
    'LS_ID' => '231,335',
    'viewers' => 1,
  ),
  269 => 
  array (
    'id' => 100544,
    'product_description' => 'Hidden reflections. Rectangular looking glass swings open to reveal three glass shelves for storing everyday bath essentials. Pure extruded aluminum frame with black finish resists corrosion to boot. Mounting hardware is included. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Infinity Black Medicine Cabinet 18"x27"',
    'product_sku' => '213876',
    'brand_name' => 'CB2',
    'price' => '329',
    'was_price' => '329',
    'image' => '/cb2/_images/main/infitiy-black-small-bath-wall-cabinet.jpg',
    'LS_ID' => '793',
    'viewers' => 1,
  ),
  270 => 
  array (
    'id' => 37019,
    'product_description' => 'Main landing. A center staple for any kitchen. Designed by Mark Daniel of Slate Design, grey washed wood frame forms nooks and cubbies of all sizes for added kitchen storage. A large slab of honed white marble with grey veining tops the kitchen island, overhanging on one side for counter-height stools and chairs to pull up. Learn about Mark Daniel on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'White Marble Top Island Grey Wood',
    'product_sku' => '223097',
    'brand_name' => 'CB2',
    'price' => '1899',
    'was_price' => '1799',
    'image' => '/cb2/img/MarbleIslandGreyWoodSHS19_1x1web_spill_item190410160836white-marble-top-island-grey-wood.jpgwhite-marble-top-island-grey-wood.jpg',
    'LS_ID' => '535',
    'viewers' => 1,
  ),
  271 => 
  array (
    'id' => 27772,
    'product_description' => 'Exquisite solid American walnut takes the finest form in Apex, with a slim reverse-beveled top that balances on a sculptural, crisscrossed tripod of notched and angled beams. ',
    'product_status' => 'active',
    'product_name' => 'Apex 51" Round Dining Table',
    'product_sku' => '234706',
    'brand_name' => 'Crate&Barrel',
    'price' => '2199',
    'was_price' => '2199',
    'image' => '/cnb/images/main/apex-51-round-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 1,
  ),
  272 => 
  array (
    'id' => 37005,
    'product_description' => 'go with the flow. Ancient Roman aqueducts and high-speed trains are the ticket to this sleek lacquered table from the minds at Italy\'s Euga Design Studio. Table flows continuous in hi-gloss white, from smooth engineered wood top to solid rubberwood legs. Fluid curve of each leg mirrors the sweeping arched structure of the aqueducts—an architectural foundation for a stretch of Eurail tracks. Comfortably dines six, but will accommodate eight. Learn about Euga Design Studio on our blog.',
    'product_status' => 'active',
    'product_name' => 'Aqua Virgo Dining Table',
    'product_sku' => '254317',
    'brand_name' => 'CB2',
    'price' => '599',
    'was_price' => '599',
    'image' => '/cb2/_images/main/aqua-virgo-dining-table.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  273 => 
  array (
    'id' => 37111,
    'product_description' => '24/7. Convertible three-position seater/sleeper is the am/pm solution for the space-challenged or the friendly out-of-towner. By day it\'s an upright but comfy lounge that can also angle back 45 degrees for TV/reading. Shut-eye rolls around, the back flips all the way down and voilà…a worthy platform bed with real pocketed coil mattress innersprings that sleeps two. Tailored neutral in gravel grey textured weave channeled with a baseball topstitch and neat flange along the edge. Powdercoated hand-welded metal and mesh frame; sleek polished chrome base. High-density foam with pocketed coil inner-springs. 100% polyester fabric: gravel grey.',
    'product_status' => 'active',
    'product_name' => 'Flex Gravel Sleeper Sofa',
    'product_sku' => '283934',
    'brand_name' => 'CB2',
    'price' => '1099',
    'was_price' => '999',
    'image' => '/cb2/_images/main/flex-gravel-sleeper-sofa.jpg',
    'LS_ID' => '205,201',
    'viewers' => 1,
  ),
  274 => 
  array (
    'id' => 98138,
    'product_description' => 'Verge refines the look of natural wood with a deep, dark charcoal finish and an undulating live-edge look. Made of richly grained mindi wood veneer, the sleek dining table shows off its perfect proportions with a dark steel v-shaped trestle base that not only adds eye-catching geometry but also gives chairs placed at the table\'s ends a bit more legroom. Verge, designed by Bill Eastburn, seats 10 and is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Verge 80" Black Live Edge Dining Table',
    'product_sku' => '296094',
    'brand_name' => 'Crate&Barrel',
    'price' => '1399',
    'was_price' => '1399',
    'image' => '/cnb/images/main/verge-80-black-live-edge-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 1,
  ),
  275 => 
  array (
    'id' => 45184,
    'product_description' => 'There\'s no better place for kids to get comfy than our Large Dark Green Nod Chair. It has a removable cover that is machine washable, so it\'s a breeze to clean up. Plus, firm, polyfoam construction gives the cozy chair support and durability. With easy assembly and loads of cover styles to choose from, there\'s never been a better time for kids to have a seat.',
    'product_status' => 'active',
    'product_name' => 'Large Dark Green Nod Chair',
    'product_sku' => '305075',
    'brand_name' => 'Crate&Barrel',
    'price' => '119',
    'was_price' => '119',
    'image' => '/cnb/images/main/new-large-dark-green-nod-chair.jpg',
    'LS_ID' => '911,99',
    'viewers' => 1,
  ),
  276 => 
  array (
    'id' => 97632,
    'product_description' => 'Using remarkable joinery techniques, skilled woodworkers expertly fit sections of solid chestnut wood to fashion Cullen\'s Scandinavian modern curves. Showcasing stunning exposed joinery details, the dining chair embraces with a barrel back that flows into armrests. The slim, padded seat perfectly mimics the frame\'s circular profile and is neatly upholstered in a top-grain leather. The warm brown pairs beautifully with the chair\'s warm brown shiitake finish. The Cullen Shiitake Round Back Dining Chair is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Cullen Shiitake Whiskey Round Back Dining Chair',
    'product_sku' => '311592',
    'brand_name' => 'Crate&Barrel',
    'price' => '499',
    'was_price' => '499',
    'image' => '/cnb/images/main/cullen-shiitake-whiskey-round-back-dining-chair.jpg',
    'LS_ID' => '510',
    'viewers' => 1,
  ),
  277 => 
  array (
    'id' => 27752,
    'product_description' => 'Kesling adds depth and dimension to the dining room, featuring a sculptural hexagon base that impresses at any angle. Made of a plantation-grown mango wood, the round table uses a coal grey finish to bring out the variety of rich color and unique graining. Pedestal base table seats six in the round.',
    'product_status' => 'active',
    'product_name' => 'Kesling 60" Round  Wood Dining Table',
    'product_sku' => '318587',
    'brand_name' => 'Crate&Barrel',
    'price' => '1199',
    'was_price' => '1199',
    'image' => '/cnb/images/main/kesling-60-round-wood-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 1,
  ),
  278 => 
  array (
    'id' => 36994,
    'product_description' => 'Part of Mermelada Estudio\'s mix and match collection, brass-finished steel sets a warm table base with angled legs that taper to the feet. Mix with the acacia wood, white marble, glass or concrete top for a fresh take on everyday dining. Learn about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Harper Brass Dining Table Base',
    'product_sku' => '321585',
    'brand_name' => 'CB2',
    'price' => '649',
    'was_price' => '649',
    'image' => '/cb2/_images/main/harper-brass-dining-table-base.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  279 => 
  array (
    'id' => 26417,
    'product_description' => 'Our Gallery Upholstered Daybed has tufted panels with top stitch detailing that supplies a sophisticated touch. Featuring a neutral wood base in a stylish light grey finish, it\'s pretty much a work of art for your bedroom, living room or kids room.',
    'product_status' => 'active',
    'product_name' => 'Gallery Grey Upholstered Daybed',
    'product_sku' => '329244',
    'brand_name' => 'Crate&Barrel',
    'price' => '999.94',
    'was_price' => '1299',
    'image' => '/cnb/images/main/gallery-grey-upholstered-daybed.jpg',
    'LS_ID' => '941',
    'viewers' => 1,
  ),
  280 => 
  array (
    'id' => 28409,
    'product_description' => 'The statement-making Strut work table offers clean, modern lines elegant enough for the dining room. Dramatically-angled reclaimed teak legs with a matte lacquer finish float a substantial glass top. ',
    'product_status' => 'active',
    'product_name' => 'Strut Teak Glass Top Table 70"',
    'product_sku' => '329926',
    'brand_name' => 'Crate&Barrel',
    'price' => '799',
    'was_price' => '799.99',
    'image' => '/cnb/images/main/strut-teak-glass-top-table-70.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  281 => 
  array (
    'id' => 98147,
    'product_description' => 'Our beautiful Yukon table unites planks of solid acacia wood with exposed butterfly joints, bringing live-edge curves and warm wood tones to the casual dining room. Angled, U-shaped legs, fashioned from steel and finished in matte antique black, provide modern counterpoint. ',
    'product_status' => 'active',
    'product_name' => 'Yukon Natural 80" Dining Table',
    'product_sku' => '335351',
    'brand_name' => 'Crate&Barrel',
    'price' => '1599',
    'was_price' => '1599',
    'image' => '/cnb/images/main/yukon-natural-80-dining-table.jpg',
    'LS_ID' => '551',
    'viewers' => 1,
  ),
  282 => 
  array (
    'id' => 51671,
    'product_description' => 'It\'s all happening – your to-do list, that is. Designed by Mark Daniel of Slate Design, rich dark green velvet office chair with pleated channels sits atop brushed brass base. Gas lift and casters make it easy to adjust height and placement. Learn about Mark Daniel on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Channel Green Velvet Office Chair',
    'product_sku' => '356277',
    'brand_name' => 'CB2',
    'price' => '599',
    'was_price' => '599',
    'image' => '/cb2/_images/main/channel-green-velvet-office-chair.jpg',
    'LS_ID' => '610,210,99',
    'viewers' => 1,
  ),
  283 => 
  array (
    'id' => 51459,
    'product_description' => 'We edged up the classic Chesterfield sofa with clean modern lines and chic grey upholstery. Out with the traditional curved arms and in with a minimal squared-off frame. Back and sides are fully tufted. Long enough to stretch out for a nap, spacious frame sits on square chrome-plated legs—an ultra-modern detail we couldn\'t resist. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Savile Slate Tufted Sofa',
    'product_sku' => '357578',
    'brand_name' => 'CB2',
    'price' => '1299',
    'was_price' => '1299',
    'image' => '/cb2/_images/main/savile-slate-tufted-sofa.jpg',
    'LS_ID' => '201',
    'viewers' => 1,
  ),
  284 => 
  array (
    'id' => 51476,
    'product_description' => 'Everyone, chill: Designed by Mermelada Estudio, sofa sectional lounges ultra-low with overstuffed cushions that beckon for late-night hangs. Straightforward lines keep the focus on comfort/cool-factor. Extra-large for extra houseguests. Arrange and rearrange sectional pieces to fit your changing needs. Learn more about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Lenyx Stone Extra Large Sofa',
    'product_sku' => '360311',
    'brand_name' => 'CB2',
    'price' => '1599',
    'was_price' => '1599',
    'image' => '/cb2/_images/main/lenyx-stone-extra-large-sofa.jpg',
    'LS_ID' => '201',
    'viewers' => 1,
  ),
  285 => 
  array (
    'id' => 51698,
    'product_description' => 'mod pod. Hang out and hide out on the patio in this breezy, handwoven seat. Faux wicker pod suspends a cozy, sheltering perch that stands up to the elements. In a time-intensive process that takes two artisans seven days to complete, off-white recyclable resin is handwoven on a powdercoated aluminum frame. Optional tufted cushion in weather-resistant taupe polyester tucks right in for a comfy spot to nest.',
    'product_status' => 'active',
    'product_name' => 'Pod Hanging Chair',
    'product_sku' => '404669',
    'brand_name' => 'CB2',
    'price' => '779',
    'was_price' => '739',
    'image' => '/cb2/_images/main/pod-hanging-chair.jpg',
    'LS_ID' => '210',
    'viewers' => 1,
  ),
  286 => 
  array (
    'id' => 51699,
    'product_description' => 'tuft love. Hang out and hide out on the patio. Designed to nest in our pod hanging chair, this buttonless tufted cushion stands up to the elements in weather-resistant taupe polyester. Tucks right in to create a comfy perch.',
    'product_status' => 'active',
    'product_name' => 'Pod Hanging Chair Cushion',
    'product_sku' => '412024',
    'brand_name' => 'CB2',
    'price' => '120',
    'was_price' => '60',
    'image' => '/cb2/_images/main/pod-hanging-chair-cushion.jpg',
    'LS_ID' => '210',
    'viewers' => 1,
  ),
  287 => 
  array (
    'id' => 51486,
    'product_description' => 'Parisian lines. Inspired by a French flea market find, this sofa\'s long slim lines are scaled just right for smaller spaces. Cutback retro arms, low tufted back and double seat cushions all on modern brass legs for an unexpected design twist.',
    'product_status' => 'active',
    'product_name' => 'Avec Emerald Green Apartment Sofa with Brass Legs',
    'product_sku' => '413345',
    'brand_name' => 'CB2',
    'price' => '1199',
    'was_price' => '1499',
    'image' => '/cb2/_images/main/avec-apartment-sofa-with-brass-legs.jpg',
    'LS_ID' => '201',
    'viewers' => 1,
  ),
  288 => 
  array (
    'id' => 52036,
    'product_description' => 'Saddle up. Leather with natural hide tones and markings saddles a contoured seat edged with a handsewn whipstitch and brass-painted rivets. Rides modern on hand-welded matte black iron frame that flows continuous from base to seat. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Roadhouse Leather Chair',
    'product_sku' => '414613',
    'brand_name' => 'CB2',
    'price' => '211.65',
    'was_price' => '249',
    'image' => '/cb2/_images/main/roadhouse-leather-chair.jpg',
    'LS_ID' => '510,210',
    'viewers' => 1,
  ),
  289 => 
  array (
    'id' => 51825,
    'product_description' => 'skinny genes. Hardworking coffee table is welded heavy-duty but looks light on its feet. Industrial iron with raw antiqued finish. mill coffee table is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Mill Coffee Table',
    'product_sku' => '417948',
    'brand_name' => 'CB2',
    'price' => '299',
    'was_price' => '299',
    'image' => '/cb2/_images/main/mill-coffee-table.jpg',
    'LS_ID' => '225',
    'viewers' => 1,
  ),
  290 => 
  array (
    'id' => 37115,
    'product_description' => 'split personality. Analyst-style lounge by day, dreamy guest bed by night. Easily adapts to host one or two overnighters: Stacked double mattress sleeps twin. Or, remove the top to side-by-side on the floor for an oversized queen. Turquoise basketweave is soft, chunky. Nap or read with head propped on a roll. Dry clean the removable top cover.To learn how certain items like this one can help make house guests feel welcome, check out Idea Central.',
    'product_status' => 'active',
    'product_name' => 'Lubi Turquoise Sleeper Daybed',
    'product_sku' => '419372',
    'brand_name' => 'CB2',
    'price' => '799',
    'was_price' => '899',
    'image' => '/cb2/_images/main/lubi-turquoise-sleeper-daybed.jpg',
    'LS_ID' => '342,205,206',
    'viewers' => 1,
  ),
  291 => 
  array (
    'id' => 51803,
    'product_description' => 'Open cylinder construction of slick polished chrome topped with a Carrara-style white/grey slab. Coffee table is smooth and cool with a subtle silvery glint of crystallization. Sports a low rise at just 12" off the floor. Totally customizable: top or base can be swapped out for a whole new look. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Smart Round Marble Top Coffee Table',
    'product_sku' => '421238',
    'brand_name' => 'CB2',
    'price' => '499',
    'was_price' => '399',
    'image' => '/cb2/_images/main/smart-round-marble-top-coffee-table.jpg',
    'LS_ID' => '225',
    'viewers' => 1,
  ),
  292 => 
  array (
    'id' => 51697,
    'product_description' => 'mod pod. Hang out and hide out on the patio in this breezy, handwoven seat. Faux wicker pod suspends a cozy, sheltering perch that stands up to the elements. In a time-intensive process that takes two artisans seven days to complete, off-white recyclable resin is handwoven on a powdercoated aluminum frame. Tufted cushion in weather-resistant taupe polyester tucks right in for a comfy spot to nest.',
    'product_status' => 'active',
    'product_name' => 'Pod Hanging Chair with Cushion',
    'product_sku' => '427978',
    'brand_name' => 'CB2',
    'price' => '899',
    'was_price' => '799',
    'image' => '/cb2/_images/main/pod-hanging-chair-with-cushion.jpg',
    'LS_ID' => '210',
    'viewers' => 1,
  ),
  293 => 
  array (
    'id' => 28334,
    'product_description' => 'Lila graces the bedroom with a sophisticated mix of marble and brass. The oval nightstand features a hand-welded iron frame finished in golden brass. Marble shelves beckon with unique veining and color in an open design that welcomes a bedside lamp, an alarm clock and a stash of nighttime reading. Beautiful in the bedroom, the nightstand also works in an entryway, bath or living room. Our Lila Oval Nightstand is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Lila Oval Nightstand',
    'product_sku' => '432013',
    'brand_name' => 'Crate&Barrel',
    'price' => '349',
    'was_price' => '349',
    'image' => '/cnb/images/main/lila-oval-nightstand.jpg',
    'LS_ID' => '225,335',
    'viewers' => 1,
  ),
  294 => 
  array (
    'id' => 27742,
    'product_description' => 'Gather \'round our elegant Aniston extension table in striking black. Stacked circles of sustainably sourced solid wood create a subtly stepped base that rises to a slightly flared post. Finished in dramatically grained oak veneer, the round tabletop expands to create an oval that seats six. Placed in a dining room, breakfast nook or sitting room, our exclusive extendable table provides flexible seating while anchoring the space with its classic style.',
    'product_status' => 'active',
    'product_name' => 'Aniston Black 45" Round Extension Dining Table',
    'product_sku' => '440794',
    'brand_name' => 'Crate&Barrel',
    'price' => '599',
    'was_price' => '599',
    'image' => '/cnb/images/main/aniston-black-45-round-extension-dining-table.jpg',
    'LS_ID' => '552',
    'viewers' => 1,
  ),
  295 => 
  array (
    'id' => 27728,
    'product_description' => 'Gather \'round our elegant Aniston extension table in classical white. Stacked circles of sustainably sourced solid wood create a subtly stepped base that rises to a slightly flared post, calling to mind Greek columns. Finished in dramatically grained oak veneer, the round tabletop expands to create an oval that seats six. Our exclusive extendable table offers flexible seating while anchoring the space with its classic presence, whether placed in a dining room, breakfast nook or sitting room.',
    'product_status' => 'active',
    'product_name' => 'Aniston White 45" Round Extension Dining Table',
    'product_sku' => '440862',
    'brand_name' => 'Crate&Barrel',
    'price' => '599',
    'was_price' => '599',
    'image' => '/cnb/images/main/aniston-white-45-round-extension-dining-table.jpg',
    'LS_ID' => '552',
    'viewers' => 1,
  ),
  296 => 
  array (
    'id' => 25565,
    'product_description' => 'Like a work of modern sculpture, our Winslet wood and marble console table pushes boundaries in an innovative exploration of shape, space and materials. With its multiple levels of surface and shelf space, the mixed-material table inserts a mango wood box inlaid with white marble into an open, glass-shelved tower. The linear frame of brass-finished iron enhances the clean, architectural impact of this handcrafted showpiece.',
    'product_status' => 'active',
    'product_name' => 'Winslet Wood and Marble Console Table',
    'product_sku' => '440987',
    'brand_name' => 'Crate&Barrel',
    'price' => '699',
    'was_price' => '699',
    'image' => '/cnb/images/main/winslet-wood-and-marble-console-table.jpg',
    'LS_ID' => '227',
    'viewers' => 1,
  ),
  297 => 
  array (
    'id' => 57872,
    'product_description' => 'marble topper. White Banswara marble contrasts dark mango wood in top form as nightstand or side table. Single drawer with minimal cutout pull and open bottom shelf provide essential storage. tux marble top nightstand is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Tux Marble Top Nightstand',
    'product_sku' => '441094',
    'brand_name' => 'CB2',
    'price' => '299',
    'was_price' => '299',
    'image' => '/cb2/_images/main/tux-marble-top-nightstand.jpg',
    'LS_ID' => '335',
    'viewers' => 1,
  ),
  298 => 
  array (
    'id' => 45352,
    'product_description' => 'We\'ve reimagined the classic bentwood chair as a backless stool with all the curvaceous appeal of the original. Channeling its roots in 19th-century Viennese cafe culture, the stool flaunts gently flared legs, an integrated footrest and an apron of graceful arches. Made of solid beechwood that\'s bent by hand, the stool graces high dining tables and kitchen islands with its charming curvilinear silhouette finished in warm walnut. ',
    'product_status' => 'active',
    'product_name' => 'Vienna Walnut Backless Counter Stool',
    'product_sku' => '445378',
    'brand_name' => 'Crate&Barrel',
    'price' => '219',
    'was_price' => '219',
    'image' => '/cnb/images/main/vienna-walnut-backless-counter-stool.jpg',
    'LS_ID' => '512',
    'viewers' => 1,
  ),
  299 => 
  array (
    'id' => 38162,
    'product_description' => 'height of white. Minimalism scales for small spaces in clean, pristine white. A shorter version of our tall stairway white desk, sleek desktop surface and one shelf ladder up in engineered wood with glossy lacquer. Slick powdercoated aluminum frame with hidden hardware accentuates spotless rise of white. Mounts sturdy to the wall. Create a vertical library with stairway white wall-mounted bookcase. Coordinate with a modern office chair for a stylish and functional space.',
    'product_status' => 'active',
    'product_name' => 'Stairway White 72.5" Desk',
    'product_sku' => '449891',
    'brand_name' => 'CB2',
    'price' => '349',
    'was_price' => '349',
    'image' => '/cb2/_images/main/stairway-white-72.5-desk.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  300 => 
  array (
    'id' => 51669,
    'product_description' => 'Hospitality-grade office chair designed by Brett Beldock pulls durability up to the table, whether tucked into a desk at home or lined up in multiples across an open-concept office space. Sloping arms soften the silhouette and add a sense of style to the workday. Learn about Brett Beldock on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Chelsea Home Office Chair',
    'product_sku' => '461977',
    'brand_name' => 'CB2',
    'price' => '299',
    'was_price' => '299',
    'image' => '/cb2/_images/main/chelsea-home-office-chair.jpg',
    'LS_ID' => '610,210',
    'viewers' => 1,
  ),
  301 => 
  array (
    'id' => 25425,
    'product_description' => 'Our 1960s-inspired Tate collection recalls the timelessness of mid-century design with tailored profiles and streamlined shapes. Crafted of solid American walnut and walnut veneer, Tate showcases gallery fronts and linear undercut drawer pulls supported by tapered turned legs. ',
    'product_status' => 'active',
    'product_name' => 'Tate Nightstand',
    'product_sku' => '470889',
    'brand_name' => 'Crate&Barrel',
    'price' => '449',
    'was_price' => '449',
    'image' => '/cnb/images/main/tate-nightstand.jpg',
    'LS_ID' => '335,430',
    'viewers' => 1,
  ),
  302 => 
  array (
    'id' => 25384,
    'product_description' => 'Gia case goods reinterpret American artisan-made furniture, updating classic curves in a streamlined design of solid maple with a rich cocoa finish. Designed by Blake Tovin, Gia simplifies tradition with updated lathe-turned legs and gently curved corners. Soft oval knobs of matte bronze cast metal continue the theme of soft curves and corners. ',
    'product_status' => 'active',
    'product_name' => 'Gia Nightstand',
    'product_sku' => '471019',
    'brand_name' => 'Crate&Barrel',
    'price' => '499',
    'was_price' => '499',
    'image' => '/cnb/images/main/gia-nightstand.jpg',
    'LS_ID' => '335,430',
    'viewers' => 1,
  ),
  303 => 
  array (
    'id' => 38143,
    'product_description' => 'Party on top, business on the bottom. Three fixed walnut veneer shelves ladder up alongside a powdercoated metal frame and pull double duty as a display/work space. Modularly designed to save room and stand next to our matching Helix walnut bookcase. Stud mounting recommended; hardware included for drywall installation. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Helix 70" Walnut Desk',
    'product_sku' => '471092',
    'brand_name' => 'CB2',
    'price' => '279',
    'was_price' => '249',
    'image' => '/cb2/_images/main/HelixShortDeskWalnutROS20_1x1.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  304 => 
  array (
    'id' => 38144,
    'product_description' => 'Party on top, business on the bottom. Three fixed walnut veneer shelves pan out in a powdercoated metal frame. Modularly designed to pull double duty as a display/work space and looks even better standing next to our Helix walnut bookcase. Stud mounting recommended; hardware included for drywall installation. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Helix 96" Walnut Desk',
    'product_sku' => '471115',
    'brand_name' => 'CB2',
    'price' => '379',
    'was_price' => '349',
    'image' => '/cb2/_images/main/HelixTallDeskWalnutROS20_1x1.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  305 => 
  array (
    'id' => 52056,
    'product_description' => 'Aggregate of cement, granite, stone, marble and natural fibers sculpt Maintain bench\'s honed beauty and natural intonations with bees wax or clear stone floor polish. Indoor and protected outdoor use. Pair with Span Bench Cushion for a plush sit. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Span Small Ivory Bench',
    'product_sku' => '475741',
    'brand_name' => 'CB2',
    'price' => '649',
    'was_price' => '549',
    'image' => '/cb2/_images/main/span-small-ivory-bench.jpg',
    'LS_ID' => '410',
    'viewers' => 1,
  ),
  306 => 
  array (
    'id' => 37114,
    'product_description' => 'split personality. Analyst-style lounge by day, dreamy guest bed by night. Easily adapts to host one or two overnighters: Stacked double mattress sleeps twin. Or, remove the top to side-by-side on the floor for an oversized queen. Silver-grey basketweave is soft, chunky. Nap or read with head propped on a roll. Dry clean the removable top cover.',
    'product_status' => 'active',
    'product_name' => 'Lubi Silver Grey Sleeper Daybed',
    'product_sku' => '476085',
    'brand_name' => 'CB2',
    'price' => '799',
    'was_price' => '899',
    'image' => '/cb2/_images/main/lubi-silver-grey-sleeper-daybed.jpg',
    'LS_ID' => '342,205,206',
    'viewers' => 1,
  ),
  307 => 
  array (
    'id' => 57815,
    'product_description' => '',
    'product_status' => 'active',
    'product_name' => 'Smart Chrome C Table with White Marble Top',
    'product_sku' => '491916',
    'brand_name' => 'CB2',
    'price' => '179',
    'was_price' => '179',
    'image' => '/cb2/_images/main/smart-marble-top-c-table.jpg',
    'LS_ID' => '226',
    'viewers' => 1,
  ),
  308 => 
  array (
    'id' => 38160,
    'product_description' => 'Pitch black. Minimalism scales for small spaces in rich black. A shorter version of  a CB2 favorite, sleek desktop surface and one shelf ladder up in engineered wood with dark lacquer. Powdercoated aluminum frame with hidden hardware accentuates spotless rise. Mounts sturdy to the wall. Create a vertical library with Stairway Black Wall Mounted Bookcase. CB2 exclusive. Pair with a modern office chair for a chic and functional space.',
    'product_status' => 'active',
    'product_name' => 'Stairway Black 72.5" Desk',
    'product_sku' => '509963',
    'brand_name' => 'CB2',
    'price' => '349',
    'was_price' => '349',
    'image' => '/cb2/_images/main/stairway-black-72.5-desk.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  309 => 
  array (
    'id' => 38161,
    'product_description' => 'Pitch black. Minimalism scales for small spaces in rich black. A taller version of a CB2 favorite, sleek desktop surface and three shelves ladder up in engineered wood with dark lacquer. Powdercoated aluminum frame with hidden hardware accentuates spotless rise. Mounts sturdy to the wall. Create a vertical library with stairway Black Wall Mounted Bookcase. CB2 exclusive. Pair with a modern office chair for a chic and functional space.',
    'product_status' => 'active',
    'product_name' => 'Stairway Black 96" Desk',
    'product_sku' => '510004',
    'brand_name' => 'CB2',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cb2/_images/main/stairway-black-96-desk.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  310 => 
  array (
    'id' => 36947,
    'product_description' => 'FSC-certified teak sets a balmy tone for this gorgeous left arm sectional by Brett Beldock. Two bench cushions stretch out in a grey woven stripe pattern for uninterrupted lounging while matching pillows pad the corner and back. On the L-shaped frame, brass details shine in the sun. Learn more about Brett Beldock on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Turn Teak Left Arm/Left Arm Daybed Sectional',
    'product_sku' => '560764',
    'brand_name' => 'CB2',
    'price' => '3998',
    'was_price' => '3998',
    'image' => '/cb2/_images/main/turn-teak-left-arm-left-arm-daybed-sectional.jpg',
    'LS_ID' => '202',
    'viewers' => 1,
  ),
  311 => 
  array (
    'id' => 36946,
    'product_description' => 'FSC-certified teak sets a balmy tone for this gorgeously long sectional by Brett Beldock. Two plush bench cushions stretch all the way out in a grey woven stripe pattern for uninterrupted lounging while matching pillows pad the back. Along the frame, brass details shine in the sun. Learn more about Brett Beldock on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Turn Teak Left Arm/Right Arm Daybed Sectional',
    'product_sku' => '560798',
    'brand_name' => 'CB2',
    'price' => '3998',
    'was_price' => '3998',
    'image' => '/cb2/_images/main/turn-teak-left-arm-right-arm-daybed-sectional.jpg',
    'LS_ID' => '202',
    'viewers' => 1,
  ),
  312 => 
  array (
    'id' => 97915,
    'product_description' => 'Our exclusive Blake Tovin design edits down the elaborate chandelier to its sleekest elements, sending out six gracefully thin tubes of painted-brass steel from its central column.',
    'product_status' => 'active',
    'product_name' => 'Clive 6-Arm Brass Chandelier',
    'product_sku' => '562687',
    'brand_name' => 'Crate&Barrel',
    'price' => '399',
    'was_price' => '399',
    'image' => '/cnb/images/main/clive-brass-chandelier.jpg',
    'LS_ID' => '1126',
    'viewers' => 1,
  ),
  313 => 
  array (
    'id' => 50403,
    'product_description' => 'With a sophisticated shelter-arm design, graywashed legs and a tufted back, our Myles sofa is dressed to impress. Bronze-hued nail heads perfectly complement the tweed-inspired taupe fabric and clean lines. This sleek piece will provide comfort and style through years of cozy evenings.',
    'product_status' => 'active',
    'product_name' => 'Taupe Tufted Nail Head Myles Sofa',
    'product_sku' => '565721',
    'brand_name' => 'World Market',
    'price' => '899.99',
    'was_price' => '899.99',
    'image' => '/nw/images/85093_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '201',
    'viewers' => 1,
  ),
  314 => 
  array (
    'id' => 50310,
    'product_description' => 'With a softly curving eucalyptus wood frame and a charcoal gray woven nautical-rope seat, our Aimee counter stool offers a unique modern spin on a classic design. Its shapely silhouette makes a stylish, versatile statement in your dining area.',
    'product_status' => 'active',
    'product_name' => 'Charcoal Gray Woven Aimee Counter Stool',
    'product_sku' => '566666',
    'brand_name' => 'World Market',
    'price' => '199.99',
    'was_price' => '199.99',
    'image' => '/nw/images/85150_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '512',
    'viewers' => 1,
  ),
  315 => 
  array (
    'id' => 100032,
    'product_description' => 'The inherent drama of a classic Persian rug goes contemporary in an ultra-modern black-on-black interpretation of traditional motifs. Centered on an ornate medallion, botanicals bloom and vine their way around Surina in a striking tonal design. Mesmerizing shades of black enliven the background, creating the artfully distressed look of an heirloom. Our Surina black rug is a Crate and Barrel exclusive.',
    'product_status' => 'active',
    'product_name' => 'Surina Black Rug 6\'x9\'',
    'product_sku' => '567435',
    'brand_name' => 'Crate&Barrel',
    'price' => '899',
    'was_price' => '899',
    'image' => '/cnb/images/main/surina-black-rug-6x9.jpg',
    'LS_ID' => '1130',
    'viewers' => 1,
  ),
  316 => 
  array (
    'id' => 49822,
    'product_description' => 'All-marble table by Mermelada Estudio is a true piece of art. White and grey veins wave across the grey marble top and down the rounded solid marble base in a gorgeous display of natural beauty. Designed with marble known for its bold and active veining, each table will be truly unique. Learn more about Mermelada Estudio on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Babylon Round Small Table',
    'product_sku' => '584087',
    'brand_name' => 'CB2',
    'price' => '1499',
    'was_price' => '1499',
    'image' => '/cb2/_images/main/babylon-round-small-table.jpg',
    'LS_ID' => '551',
    'viewers' => 1,
  ),
  317 => 
  array (
    'id' => 49131,
    'product_description' => 'Lighter mahogany desk designed by Caleb Zipperer is a work of art. Contrasting black solid wood legs support a mahogany veneer top with gorgeous grain and two drawers for storage. Sleek from all angles, can be floated to divide/make a room. Learn about Caleb Zipperer on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Kent Mahogany Desk',
    'product_sku' => '584799',
    'brand_name' => 'CB2',
    'price' => '1199',
    'was_price' => '1199',
    'image' => '/cb2/_images/main/kent-mahogany-desk.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  318 => 
  array (
    'id' => 49728,
    'product_description' => 'Two-tone nightstand by Zak Rose mixes materials in a nod to heirlooms and history. Lacquered ivory drawer fronts and brass-finished hardware contrast with bookmatched walnut veneer in detail-rich, vintage-inspired design. Learn about Zak Rose on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Paterson Lacquered Ivory Nightstand',
    'product_sku' => '586743',
    'brand_name' => 'CB2',
    'price' => '449',
    'was_price' => '449',
    'image' => '/cb2/_images/main/paterson-lacquered-ivory-nightstand.jpg',
    'LS_ID' => '335',
    'viewers' => 1,
  ),
  319 => 
  array (
    'id' => 51719,
    'product_description' => 'lounge act. The place to see and be seen poolside, fireside, outside. From Italian design team ivdesign.it, modern modular lounge is open to your choice of social arrangements with four a la carte components: loveseat, armless chair, corner chair and coffee table. Floats low profile on rust-resistant aluminum frame powdercoated hi-gloss white that aligns at the same height across the board, from seating to table. Ultra sturdy, but extremely light to lift, move around, store out of season. Textured taupe poly weave seat and plenty of loose back pillows invite laid-back lazing. Removable covers spot clean. Learn about Francesca Braga Rosa + Ivano Vianello = ivdesign.it on our blog. casbah corner chair is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Casbah Corner Chair',
    'product_sku' => '593117',
    'brand_name' => 'CB2',
    'price' => '549',
    'was_price' => '549',
    'image' => '/cb2/_images/main/casbah-corner-chair.jpg',
    'LS_ID' => '210',
    'viewers' => 1,
  ),
  320 => 
  array (
    'id' => 52052,
    'product_description' => 'Chair designed by estudiobola takes cues from Brazilian midcentury design for a piece that expertly marries both vintage and modern. Brown mid-tone leather will soften and wear beautifully over time, stitched together into form and complete with a nickel-plated base. Seat is removable/adjustable for optimal comfort. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Sottile Brown Chair',
    'product_sku' => '596331',
    'brand_name' => 'CB2',
    'price' => '469',
    'was_price' => '469',
    'image' => '/cb2/_images/main/sottile-brown-chair.jpg',
    'LS_ID' => '510,210',
    'viewers' => 1,
  ),
  321 => 
  array (
    'id' => 37051,
    'product_description' => 'designer picnic. Jonas Wahlström\'s outdoor dining table is a minimalist feast for the design eye. Crafted in solid acacia wood, a span of seven full-length planks is detailed at the corners with legs precisely notched and integrated flush into the top. Built-in umbrella hole sits ready at the center to bring on the shade. Multi-step finishing process leaves a subtle grey wash adding unique dimension to the wood. Will weather to a silvery-grey patina if left unprotected. Learn about Jonas Wahlström on our blog.',
    'product_status' => 'active',
    'product_name' => 'Matera Grey Dining Table',
    'product_sku' => '604873',
    'brand_name' => 'CB2',
    'price' => '499',
    'was_price' => '499',
    'image' => '/cb2/_images/main/matera-dining-table.jpg',
    'LS_ID' => '850',
    'viewers' => 1,
  ),
  322 => 
  array (
    'id' => 51782,
    'product_description' => 'black out. Super-sized round hits the deck in outdoor-friendly materials that can stand up (or sit down) to the elements. Chunky cross-stitch ropes in the sides, tight concentric weave rounds out the top—all in black poly/foam knit that\'s easy to wipe clean. Dense polyfil makes it sturdy for seat/ottoman duty, or table it with a tray on top. criss knit black pouf is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Criss Knit Black Pouf',
    'product_sku' => '605986',
    'brand_name' => 'CB2',
    'price' => '199',
    'was_price' => '199',
    'image' => '/cb2/_images/main/criss-knit-black-pouf.jpg',
    'LS_ID' => '222',
    'viewers' => 1,
  ),
  323 => 
  array (
    'id' => 52055,
    'product_description' => 'Designed by Brett Beldock, modern acrylic base meets cozy sheepskin seat to create a bench that makes a statement without overwhelming the room. Cloud of plush sheepskin seems to float on two slim acrylic legs that disappear below. Learn about Brett Beldock on our blog. CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Acrylic Sheepskin Bench',
    'product_sku' => '613325',
    'brand_name' => 'CB2',
    'price' => '1199',
    'was_price' => '1199',
    'image' => '/cb2/_images/main/acrylic-sheepskin-bench.jpg',
    'LS_ID' => '410',
    'viewers' => 1,
  ),
  324 => 
  array (
    'id' => 45070,
    'product_description' => 'Stay active as you sit. Designed to keep you physically engaged while you\'re working from home, the ingenious Ballo updates the balance ball into an office-appropriate chair. With its clean lines in chic neutrals, the grey and white desk stool accents your home office with playful yet professional good looks while encouraging movement. The counterweighted base helps keep the Ballo chair upright, with the dimpled, non-slip seat offering comfort and stability. Adjust the level of inflation with the included air pump to get just the right level of bounce and support, and use the handholds to tote the stool to wherever you need to work.',
    'product_status' => 'active',
    'product_name' => 'Humanscale ® Grey Ballo ™ Chair',
    'product_sku' => '664897',
    'brand_name' => 'Crate&Barrel',
    'price' => '239',
    'was_price' => '299',
    'image' => '/cnb/images/main/humanscale-grey-ballo-chair.jpg',
    'LS_ID' => '610',
    'viewers' => 1,
  ),
  325 => 
  array (
    'id' => 26134,
    'product_description' => 'Our classic table in a bag unfolds in grey-finished mahogany to a low profile for beach blanket bingo or casual dining at outdoor concerts or picnics. Easy-assembly folding table comes with a charcoal canvas carrying bag. Mahogany is certified by the Forest Stewardship Council (FSC)—the "gold standard" designation for wood harvested from forests that are responsibly managed, socially beneficial, environmentally appropriate and economically viable.',
    'product_status' => 'active',
    'product_name' => 'Table In A Bag',
    'product_sku' => '670848',
    'brand_name' => 'Crate&Barrel',
    'price' => '59.95',
    'was_price' => '59.95',
    'image' => '/cnb/images/main/table-in-a-bag.jpg',
    'LS_ID' => '850',
    'viewers' => 1,
  ),
  326 => 
  array (
    'id' => 66037,
    'product_description' => 'fine lines. MASHstudios goes against the grain with long, low-laying storage in acacia veneer. Four elongated drawers glide silently with subtle pulls. Recessed iron legs with brushed nickel finish add industrial gleam to organic form. Learn about MASHstudios on our blog. linear low dresser is a CB2 exclusive.',
    'product_status' => 'active',
    'product_name' => 'Linear Low Dresser',
    'product_sku' => '673851',
    'brand_name' => 'CB2',
    'price' => '749',
    'was_price' => '699',
    'image' => '/cb2/_images/main/linear-low-dresser.jpg',
    'LS_ID' => '232,334,99',
    'viewers' => 1,
  ),
  327 => 
  array (
    'id' => 51142,
    'product_description' => 'Featuring clean lines and solid oak wood legs and arms washed in antique gray, our dining armchair gets a modern touch with gray chenille fabric upholstery. A handsome choice for accent seating, this versatile chair is a sleek update to any dining or living area.',
    'product_status' => 'active',
    'product_name' => 'Gray Wood Monroe Upholstered Dining Armchair',
    'product_sku' => '57002885',
    'brand_name' => 'World Market',
    'price' => '279.99',
    'was_price' => '279.99',
    'image' => '/nw/images/90525_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '510',
    'viewers' => 1,
  ),
  328 => 
  array (
    'id' => 58051,
    'product_description' => 'Featuring mid-century-inspired hairpin legs in gold iron and an acacia-wood top with a real live edge, this dining table adds minimalist appeal to your kitchen or dining space. The natural tabletop has unique knots and cracks, ensuring you get a one-of-a-kind piece for your home. A clean, modern take on luxurious style, this table instantly elevates your dining experience.',
    'product_status' => 'active',
    'product_name' => 'Live Edge Acacia Wood and Gold Hairpin Madison Dining Table',
    'product_sku' => '57003323',
    'brand_name' => 'World Market',
    'price' => '499.99',
    'was_price' => '499.99',
    'image' => '/nw/images/93477_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '551',
    'viewers' => 1,
  ),
  329 => 
  array (
    'id' => 58114,
    'product_description' => 'Featuring an antiqued bamboo surface with a geometric steel frame in dark espresso, the Tristan dining bench is an industrial-style statement piece. Its bold angular legs are reinforced for reliable stability and the rustic woodgrain of the seat is a warm invitation for two diners or impromptu guests. Coordinate this modern bench with an industrial-style dining table to create a timeless gathering place.',
    'product_status' => 'active',
    'product_name' => 'Wood and Metal Industrial Tristan Dining Bench',
    'product_sku' => '57003437',
    'brand_name' => 'World Market',
    'price' => '229.99',
    'was_price' => '229.99',
    'image' => '/nw/images/94588_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '511',
    'viewers' => 1,
  ),
  330 => 
  array (
    'id' => 50345,
    'product_description' => 'These Harper counter stools feature flared bentwood legs and brown faux-leather seats that swivel a full 360-degrees. Counter-height backless chairs in a sophisticated mid-century style, these plush and cozy swiveling seats are fitted with black metal footrests. Versatile extra seating designed to make an impact while entertaining, this set of two counter stools are a sharp update for the dining room, kitchen or home bar.',
    'product_status' => 'active',
    'product_name' => 'Brown Faux Leather Harper Swivel Counter Stools Set of 2',
    'product_sku' => '57003940',
    'brand_name' => 'World Market',
    'price' => '299.99',
    'was_price' => '299.99',
    'image' => '/nw/images/95139_XXX_v1.tif&wid=2000&cvt=jpeg',
    'LS_ID' => '512',
    'viewers' => 1,
  ),
  331 => 
  array (
    'id' => 35402,
    'product_description' => 'Pieces of natural agate are arranged in an artful pattern then fused together
to create this stylish table\'s top. Like jewelry for your room, it instantly
dresses up bedsides and living rooms. Each piece is subtly one of a kind.

  * 13"diam. x 20"h.
  * Pieced natural agate and laminate top.
  * Due to the natural materials used, variations in color and shading are to be expected.
  * Metal base in an Antique Brass finish.
  * Made in India.',
    'product_status' => 'active',
    'product_name' => 'Agate Side Table',
    'product_sku' => 'agate-side-table-h2335',
    'brand_name' => 'West Elm',
    'price' => '299',
    'was_price' => '299',
    'image' => '/westelm/westelm_images/agate-side-table-h2335_main.jpg',
    'LS_ID' => '226',
    'viewers' => 1,
  ),
  332 => 
  array (
    'id' => 89080,
    'product_description' => 'With a frame that extends beyond the mirror, this decorative wall mirror takes
on a three dimensional aura and puts the focus on what matters most—your
style. Use individually or combine multiple for a chic, accented display.

###### KEY DETAILS

  * Engineered wood in a Walnut finish.
  * Mirrored glass.
  * Mounting hardware included.
  * Sold individually or as a set of 3.
  * Combine multiple mirrors for a chic display.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Maren Wood Mirrors',
    'product_sku' => 'maren-wood-mirrors-d9100',
    'brand_name' => 'West Elm',
    'price' => '19-87',
    'was_price' => '19-87',
    'image' => '/westelm/westelm_images/maren-wood-mirrors-d9100_main.jpg',
    'LS_ID' => '1110',
    'viewers' => 1,
  ),
  333 => 
  array (
    'id' => 100531,
    'product_description' => 'Inspired by relaxed coastal vibes, our Marin Sleeper Sofa\'s thick, comfy
cushions are the softest seat in the house. Whether you curl up or sprawl out, its oversized frame is built for some serious lounging. The best part? It
easily folds out to a Queen-sized sleeper: A no-stress solution when company calls.',
    'product_status' => 'active',
    'product_name' => 'Marin Sleeper Sofa',
    'product_sku' => 'marin-sleeper-sofa-h6954',
    'brand_name' => 'West Elm',
    'price' => '2239-2599',
    'was_price' => '2239-2599',
    'image' => '/westelm/westelm_images/marin-sleeper-sofa-h6954_main.jpg',
    'LS_ID' => '205,201',
    'viewers' => 1,
  ),
  334 => 
  array (
    'id' => 49745,
    'product_description' => 'Supreme comfort, naturally. Made from jute, a natural and renewable fiber,
this cushion is the perfect companion to the Terra Bench.

  * Woven of braided jute shell.
  * Foam fill.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Terra Bench Cushion',
    'product_sku' => 'terra-bench-cushion-r811',
    'brand_name' => 'West Elm',
    'price' => '59.99',
    'was_price' => '99',
    'image' => '/westelm/westelm_images/terra-bench-cushion-r811_main.jpg',
    'LS_ID' => '511',
    'viewers' => 1,
  ),
  335 => 
  array (
    'id' => 35977,
    'product_description' => 'Our Wood Tiled Nightstand features whitewashed hand-inlaid wooden tiles, a
smooth lacquered body and understated Antique Bronze-finished hardware. It
makes a light and airy addition next to the bed or sofa-side.',
    'product_status' => 'active',
    'product_name' => 'Wood Tiled Nightstand',
    'product_sku' => 'wood-tiled-nightstand-g615',
    'brand_name' => 'West Elm',
    'price' => '349-698',
    'was_price' => '349-698',
    'image' => '/westelm/westelm_images/wood-tiled-nightstand-g615_main.jpg',
    'LS_ID' => '335',
    'viewers' => 1,
  ),
  336 => 
  array (
    'id' => 36058,
    'product_description' => 'Brought to you in collaboration with Pottery Barn Kids, our GREENGUARD Gold
Certified Modern Nursery Collection marries clean lines with durable
craftsmanship. Covered in child-safe, nontoxic finishes, this versatile crib
converts into a toddler bed with the matching conversion kit (sold
separately). It\'s crafted in a Fair Trade Certified™ facility, directly
supporting the workers who make it.

**KEY DETAILS**

  * Kiln-dried poplar wood frame and ash wood base.
  * Finished in child-safe 60% water-based White paint and 100% water-based Pecan stain.
  * Metal mattress platform has two height options to accommodate your growing baby (mattress sold separately).
  * Easily adapts from crib to toddler bed (conversion kit sold separately). 
  * [GREENGUARD Gold Certified.](http://greenguard.org/en/CertificationPrograms/CertificationPrograms_childrenSchools.aspx) Meets or exceeds stringent chemical and VOC emissions standards.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * Made in a [Fair Trade Certified™](https://www.westelm.com/pages/about-west-elm/our-commitments/fair-trade/) facility in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Modern Convertible Crib',
    'product_sku' => 'modern-convertible-crib-h3215',
    'brand_name' => 'West Elm',
    'price' => '149-699',
    'was_price' => '149-699',
    'image' => '/westelm/westelm_images/modern-convertible-crib-h3215_main.jpg',
    'LS_ID' => '942',
    'viewers' => 1,
  ),
  337 => 
  array (
    'id' => 36142,
    'product_description' => 'Inspired by Scandinavian modernism, our Modern Desk pairs a sleek body with
beautifully angled legs. Its wide desktop offers plenty of space to work while
three drawers help keep everything organized.

  * 50"w x 24"d x 30"h.
  * Pecan-finished solid wood legs.
  * White lacquered engineered wood body.
  * Metal hardware in a Gunmetal finish.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Modern Desk',
    'product_sku' => 'modern-desk-white-pecan-h1497',
    'brand_name' => 'West Elm',
    'price' => '899',
    'was_price' => '899',
    'image' => '/westelm/westelm_images/modern-desk-white-pecan-h1497_main.jpg',
    'LS_ID' => '660',
    'viewers' => 1,
  ),
  338 => 
  array (
    'id' => 99839,
    'product_description' => 'Update your bathroom with our Seamless Medicine Cabinet. Its simple,
streamlined frame blends in with any decor. Behind the mirror, three glass
shelves bring order to your toiletries.',
    'product_status' => 'active',
    'product_name' => 'Seamless Medicine Cabinet',
    'product_sku' => 'seamless-medicine-cabinet-w3498',
    'brand_name' => 'West Elm',
    'price' => '300',
    'was_price' => '300',
    'image' => '/westelm/westelm_images/seamless-medicine-cabinet-w3498_main.jpg',
    'LS_ID' => '793',
    'viewers' => 1,
  ),
  339 => 
  array (
    'id' => 35991,
    'product_description' => 'With its simple construction and classic proportions, our Metalwork Nightstand
is an instant essential for any style bedroom. A hot-rolled steel finish gives
it an unexpected, industrial edge.',
    'product_status' => 'active',
    'product_name' => 'Metalwork Grand Nightstand',
    'product_sku' => 'metalwork-grand-nightstand-h4525',
    'brand_name' => 'West Elm',
    'price' => '399-798',
    'was_price' => '399-798',
    'image' => '/westelm/westelm_images/metalwork-grand-nightstand-h4525_main.jpg',
    'LS_ID' => '335',
    'viewers' => 1,
  ),
  340 => 
  array (
    'id' => 34768,
    'product_description' => 'Our super-adaptable, go-anywhere Simple Bed Frame makes an ideal first "big
kid" bed for boys and girls alike. Pair it with a headboard or use it on its
own as a platform bed.

  * Solid and engineered wood in an Acorn finish.
  * Solid pine slats for a sturdy foundation.
  * Accommodates standard mattresses with or without box spring.
  * Coordinates with most west elm headboards (sold separately).
  * Use our [Bed Builder Tool](http://www.westelm.com/shop/design-lab/build-a-perfect-bed) to match this bed frame with one of our headboards.
  * Full size includes 3 middle support legs.
  * Imported.',
    'product_status' => 'active',
    'product_name' => 'Simple Kids\' Bed Frame - Acorn',
    'product_sku' => 'simple-kids-bed-frame-acorn-h3281',
    'brand_name' => 'West Elm',
    'price' => '249-299',
    'was_price' => '249-299',
    'image' => '/westelm/westelm_images/simple-kids-bed-frame-acorn-h3281_main.jpg',
    'LS_ID' => '941',
    'viewers' => 1,
  ),
  341 => 
  array (
    'id' => 46871,
    'product_description' => 'Ensure your baby a sound—and safe—night\'s sleep with the Lullaby Crib
Mattress. It has two sides (one for infants and one for toddlers) and is both
GREENGUARD and CertiPUR-US® certified. The square corner design fits securely
within our convertible cribs and a waterproof surface makes it easy to keep
clean.

**KEY DETAILS**

  * 52"w x 27.5"d x 6"h.
  * 120 interlocking coils provide optimal support.
  * Two sided: firm side for infants and a softer, foam-cushioned side for toddlers.
  * Each side features a distinct design: The infant side is gray with white moons and stars; toddler side is white with gray moons and stars.
  * Vinyl mattress surface is waterproof and easy to keep clean.
  * All foams are CertiPUR-US® certified and made without PBDE flame retardants, mercury, lead and other heavy metals.
  * [GREENGUARD Gold Certified.](http://greenguard.org/en/CertificationPrograms/CertificationPrograms_childrenSchools.aspx) Meets or exceeds stringent chemical and VOC emissions standards.
  * Mattress edge has a square corner design for secure fit inside crib.
  * Mattress fits all of our convertible cribs (sold separately); does not fit Mini Crib.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * Please read Safety Information tab for important crib safety callouts.',
    'product_status' => 'active',
    'product_name' => 'Lullaby Crib Mattress',
    'product_sku' => 'lullaby-studio-crib-mattress-h3218',
    'brand_name' => 'West Elm',
    'price' => '169',
    'was_price' => '169',
    'image' => '/westelm/westelm_images/lullaby-studio-crib-mattress-h3218_main.jpg',
    'LS_ID' => '943',
    'viewers' => 1,
  ),
  342 => 
  array (
    'id' => 100591,
    'product_description' => 'Made from solid wood, our Aurora Dining Table\'s natural wood knots and
graining add intrigue to this everyday dining table. It comes in two sizes so
you can find the fit that\'s perfect for you.',
    'product_status' => 'active',
    'product_name' => 'Aurora Rectangle Dining Table',
    'product_sku' => 'aurora-rectangle-dining-table-h7459',
    'brand_name' => 'West Elm',
    'price' => '899-1099',
    'was_price' => '899-1099',
    'image' => '/westelm/westelm_images/aurora-rectangle-dining-table-h7459_main.jpg',
    'LS_ID' => '551',
    'viewers' => 1,
  ),
  343 => 
  array (
    'id' => 35696,
    'product_description' => 'Available in two sizes, our Lena Mid-Century Dining Table pays homage to the
streamlined style of iconic \'50s and \'60s furniture designs. Its thin frame
offers up extra legroom for family meals and cocktail parties alike.

  * Solid poplar wood in an Espresso finish.
  * Handcrafted.
  * Coordinates with west elm\'s Lena Dining Chair (sold separately).
  * Imported.
  * Your purchase of handcrafted items helps preserve craft traditions worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/handcrafted/)',
    'product_status' => 'active',
    'product_name' => 'Lena Mid-Century Dining Table',
    'product_sku' => 'lena-mid-century-dining-table-h2248',
    'brand_name' => 'West Elm',
    'price' => '499',
    'was_price' => '499',
    'image' => '/westelm/westelm_images/lena-mid-century-dining-table-h2248_main.jpg',
    'LS_ID' => '551',
    'viewers' => 1,
  ),
  344 => 
  array (
    'id' => 36065,
    'product_description' => 'Brought to you in collaboration with Pottery Barn Kids, our GREENGUARD Gold
Certified Lennox Nursery Collection combines richly grained wood with durable
craftsmanship. Made of solid eucalyptus and covered in a nontoxic, water-based
finish, this versatile crib converts into a toddler bed with the matching
conversion kit (sold separately).

###### KEY DETAILS

  * Kiln-dried solid eucalyptus wood.
  * Covered in a child-safe, water-based lacquer finish in Walnut.
  * Metal mattress platform has height options to accommodate your growing baby (mattress sold separately).
  * [GREENGUARD Gold Certified.](http://greenguard.org/en/CertificationPrograms/CertificationPrograms_childrenSchools.aspx) Meets or exceeds stringent chemical and VOC emissions standards.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * When purchasing a convertible crib, it is strongly recommended to purchase any desired conversion kits at the same time.
  * Made in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Lennox Convertible Crib - Walnut',
    'product_sku' => 'lennox-convertible-crib-walnut-d6759',
    'brand_name' => 'West Elm',
    'price' => '149-699',
    'was_price' => '149-699',
    'image' => '/westelm/westelm_images/lennox-convertible-crib-walnut-d6759_main.jpg',
    'LS_ID' => '942',
    'viewers' => 1,
  ),
  345 => 
  array (
    'id' => 34769,
    'product_description' => 'Our mid-century inspired Penelope Nightstand is made of sustainably sourced
wood in a Fair Trade Certified™ facility. Its marble top is wide enough for
stacks of bedtime reading, while a rounded drawer provides extra storage
space.',
    'product_status' => 'active',
    'product_name' => 'Penelope Nightstand - Acorn w/ Marble Top',
    'product_sku' => 'penelope-nightstand-small-acorn-h701',
    'brand_name' => 'West Elm',
    'price' => '399-798',
    'was_price' => '399-798',
    'image' => '/westelm/westelm_images/penelope-nightstand-small-acorn-h701_main.jpg',
    'LS_ID' => '335',
    'viewers' => 1,
  ),
  346 => 
  array (
    'id' => 100542,
    'product_description' => 'Inspired by the Japanese art technique of shou sugi ban, our Tanner Coffee
Table brings a textural depth to your space. Its solid hemlock wood frame is
heavily sand blasted for a charred-like surface feel and high sheen finish,
with a shapely cutout base to complete its striking look.',
    'product_status' => 'active',
    'product_name' => 'Tanner Solid Wood Coffee Table',
    'product_sku' => 'tanner-solid-wood-coffee-table-h7006',
    'brand_name' => 'West Elm',
    'price' => '399',
    'was_price' => '399',
    'image' => '/westelm/westelm_images/tanner-solid-wood-coffee-table-h7006_main.jpg',
    'LS_ID' => '225',
    'viewers' => 1,
  ),
  347 => 
  array (
    'id' => 51104,
    'product_description' => 'Crafted of oak wood that\'s sustainably sourced and kiln-dried for extra
durability, our Tahoe Dining Bench is a streamlined take on rugged farmhouse
styles. The grain of the wood peeks through the blackened finish.

###### KEY DETAILS

  * Made of solid oak wood & European white oak veneer.
  * Covered in a water-based Blackened Oak finish.
  * All wood is kiln-dried for added durability.
  * All wood is FSC®-certified. Your purchase of this product helps support forests and ecosystems worldwide. [Learn more.](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/)
  * Legs feature adjustable levelers to adapt to varying floor levels.
  * This contract grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Coordinates with our Tahoe Dining Table (sold separately).
  * Made in Indonesia.',
    'product_status' => 'active',
    'product_name' => 'Tahoe Dining Bench - Blackened Oak',
    'product_sku' => 'tahoe-dining-bench-blackened-oak-h5064',
    'brand_name' => 'West Elm',
    'price' => '499-699',
    'was_price' => '499-699',
    'image' => '/westelm/westelm_images/tahoe-dining-bench-blackened-oak-h5064_main.jpg',
    'LS_ID' => '511',
    'viewers' => 1,
  ),
  348 => 
  array (
    'id' => 52376,
    'product_description' => 'Our Hunter Shaped Wood Stacking Chair features a sleek wood finish and a
classic silhouette that will elevate your al fresco dining experience. Its
small foot print and stacking ability make it convenient for storing when the
season is over.

###### KEY DETAILS

  * 19"w x 20.6"d x 31.5"h.
  * Dark walnut finish: Frame and legs are solid walnut wood.
  * Dyed black finish: Frame and legs are solid ash wood.
  * Indoor/outdoor.
  * Plastic foot caps protect floors.
  * Stacks 6 high.
  * This contract-grade item is manufactured to meet the demands of commercial use in addition to residential. [See more](https://www.westelm.com/shop/furniture/contract-grade-furniture/).
  * Made in China.',
    'product_status' => 'active',
    'product_name' => 'Hunter Shaped Wood Stacking Chair',
    'product_sku' => 'hunter-shaped-wood-stacking-chair-h6381',
    'brand_name' => 'West Elm',
    'price' => '279.3-299',
    'was_price' => '299-399',
    'image' => '/westelm/westelm_images/hunter-shaped-wood-stacking-chair-h6381_main.jpg',
    'LS_ID' => '510',
    'viewers' => 1,
  ),
  349 => 
  array (
    'id' => 35302,
    'product_description' => 'We aimed for full-on glam while designing our Marble-Topped Pedestal Coffee
Table, framing its white marble top within a metal base. Shiny and
substantial, it feels oh-so-sophisticated. Don’t be afraid to put your feet
up.',
    'product_status' => 'active',
    'product_name' => 'Marble-Topped Pedestal Coffee Table - White Marble/Antique Brass',
    'product_sku' => 'marble-topped-pedestal-coffee-table-h1478',
    'brand_name' => 'West Elm',
    'price' => '699',
    'was_price' => '699',
    'image' => '/westelm/westelm_images/marble-topped-pedestal-coffee-table-h1478_main.jpg',
    'LS_ID' => '225',
    'viewers' => 1,
  ),
  350 => 
  array (
    'id' => 36190,
    'product_description' => 'A contemporary take on a classic design, the Polywood x West Elm Adirondack
Chair\'s curved design will will help you settle into your zone while unwinding
with a cool drink and a good read. Constructed of genuine POLYWOOD® lumber, a
proprietary blend of plastics which includes recycled milk jugs and detergent
bottles, making for an impactful and low-maintenance piece that\'s perfect for
any yard.

  * 31.25"w x 33.9"d x 38.7"h.
  * Comprised of recycled milk jugs made from high-density polyethylene plastic.
  * 100% all-weather.
  * POLYWOOD® lumber does not splinter, crack, chip, peel or rot and it is resistant to corrosive substances such as oil, salt spray and other environmental stresses.
  * Stain resistant to wine and condiment stains (just wipe clean with soap and water).
  * Coordinates with our Polywood Modern Adirondack Chair Cushion (sold separately).
  * Made in the USA from recycled materials.',
    'product_status' => 'active',
    'product_name' => 'Polywood x West Elm Adirondack Chair',
    'product_sku' => 'polywood-x-west-elm-adirondack-chair-h4677',
    'brand_name' => 'West Elm',
    'price' => '499',
    'was_price' => '499',
    'image' => '/westelm/westelm_images/polywood-x-west-elm-adirondack-chair-h4677_main.jpg',
    'LS_ID' => '813',
    'viewers' => 1,
  ),
  351 => 
  array (
    'id' => 99871,
    'product_description' => 'Simple in design, our Emmerson Round Expandable Dining Table is made from solid reclaimed pine wood and covered in a go-with-everything finish.
Comfortably seating four to six with its expandable sides, its perfect for
family dinners and guests alike.',
    'product_status' => 'active',
    'product_name' => 'Emmerson Round Expandable Dining Table',
    'product_sku' => 'emmerson-round-expandable-dining-table-h6845',
    'brand_name' => 'West Elm',
    'price' => '1799',
    'was_price' => '1799',
    'image' => '/westelm/westelm_images/emmerson-round-expandable-dining-table-h6845_main.jpg',
    'LS_ID' => '552',
    'viewers' => 1,
  ),
  352 => 
  array (
    'id' => 36052,
    'product_description' => 'Brought to you in collaboration with Pottery Barn Kids, our GREENGUARD Gold
Certified Mid-Century Nursery Collection combines timeless style with durable
craftsmanship. Made of sustainably sourced wood and covered in a nontoxic,
water-based finish, this 4-in-1 crib can grow with your child from newborn to
teenager with the matching conversion kits (sold separately). It\'s crafted in
a Fair Trade Certified™ facility, directly supporting the workers who make it.

###### KEY DETAILS

  * Kiln-dried solid eucalyptus wood and engineered wood with an Acacia wood veneer.
  * All wood is sustainably sourced and [FSC®-certified](https://www.westelm.com/pages/about-west-elm/our-commitments/sustainable/).
  * Covered in a child-safe, water-based Acorn finish.
  * Metal mattress platform has two height options to accommodate your growing baby (mattress sold separately).
  * This crib can be used in 4 ways: (1) Crib only; (2) Toddler bed (conversion kit sold separately); (3) Headboard only (metal bed frame sold separately); (4) Complete full-sized bed (conversion kit sold separately).
  * When purchasing a convertible crib, it is strongly recommended to purchase any desired conversion kits at the same time.
  * [GREENGUARD Gold Certified.](http://greenguard.org/en/CertificationPrograms/CertificationPrograms_childrenSchools.aspx) Meets or exceeds stringent chemical and VOC emissions standards.
  * Rigorously tested to meet or exceed all required and voluntary safety standards.
  * Made in a [Fair Trade Certified™](https://www.westelm.com/pages/about-west-elm/our-commitments/fair-trade/) facility in Vietnam.',
    'product_status' => 'active',
    'product_name' => 'Mid-Century 4-in-1 Convertible Crib - Acorn',
    'product_sku' => 'mid-century-4-in-1-convertible-crib-acorn-d7216',
    'brand_name' => 'West Elm',
    'price' => '149-799',
    'was_price' => '149-799',
    'image' => '/westelm/westelm_images/mid-century-4-in-1-convertible-crib-acorn-d7216_main.jpg',
    'LS_ID' => '942',
    'viewers' => 1,
  ),
  353 => 
  array (
    'id' => 36097,
    'product_description' => 'Sleek and sophisticated, our Modernist Dresser frames richly grained wood in a
polished lacquer case for a handsome contrast of matte and shine. Antique
brass-finished metal drawer pulls and pin legs finish off the entire look',
    'product_status' => 'active',
    'product_name' => 'Modernist Wood & Lacquer 8-Drawer Dresser - Winter Wood',
    'product_sku' => 'modernist-wood-lacquer-8-drawer-dresser-winter-wood-h4977',
    'brand_name' => 'West Elm',
    'price' => '1799',
    'was_price' => '1799',
    'image' => '/westelm/westelm_images/modernist-wood-lacquer-8-drawer-dresser-winter-wood-h4977_main.jpg',
    'LS_ID' => '334',
    'viewers' => 1,
  ),
  354 => 
  array (
    'id' => 35826,
    'product_description' => 'Our Silhouette Pedestal Dining Table cuts a fine figure with its richly-
variegated marble top perched on a dramatic metal base. Made from solid marble
sourced from India, the natural variations in veining mean that no two tables
are exactly alike.',
    'product_status' => 'active',
    'product_name' => 'Silhouette Pedestal Round Dining Table - White Marble/Brushed Nickel',
    'product_sku' => 'silhouette-pedestal-dining-table-round-white-marble-large-h4423',
    'brand_name' => 'West Elm',
    'price' => '1099-1699',
    'was_price' => '1099-1699',
    'image' => '/westelm/westelm_images/silhouette-pedestal-dining-table-round-white-marble-large-h4423_main.jpg',
    'LS_ID' => '551',
    'viewers' => 1,
  ),
);*/
		$response = [];
		$response_nmatch = [];
		$response_match = [];
		$response_deptsame = [];
		$response_deptother = [];
		$response_catsame = [];
		$response_catother = [];
		$response_identical = [];
		$remainarr = [];
		 
		
		foreach ($product_rows as $product) {
			         $product->image =  env('APP_URL').$product->image; 
					 
					
					if (strpos($product->LS_ID, $LSID[1]) !== false)
					{
						array_push($response_match,$product);
					}
					else{
						   array_push($response_nmatch,$product);
					}
				}
				
				$response_match = array_values(array_unique($response_match,SORT_REGULAR));
				
				/* ================== Sort By Category Start =========================== */   
				
				foreach($response_match as $cat){
				
					$LS_ID_arr = explode(",",$cat->LS_ID);
					
					for($i=0;$i<count($LS_ID_arr);$i++){
						if (strpos($LS_ID_arr[$i], $LSID[1]) !== false){
							if((strpos($LS_ID_arr[$i],$LSID[1]))==1){
								array_push($response_catsame,$cat);
							}
							else{
									array_push($response_catother,$cat);
							}
						}
					}
				
				}
				/* ================== Sort By Category End =========================== */   
				
				
					
				$response_catsame = array_values(array_unique($response_catsame,SORT_REGULAR));
				
				
				/* ================== Sort By Department Start =========================== */  
				   
				foreach($response_catsame as $dept){
				
					$LS_ID_arr = explode(",",$dept->LS_ID);
					
					for($i=0;$i<count($LS_ID_arr);$i++){
						if (strpos($LS_ID_arr[$i], $LSID[0]) !== false){
							if($LS_ID_arr[$i] == $LSID){
								array_push($response_identical,$dept);
							}
							else{
									if((strpos($LS_ID_arr[$i],$LSID[0]))==0){
										array_push($response_deptsame,$dept);
									}
									else{
											array_push($response_deptother,$dept);
									}
							}
						}
					}
				
				}
				
				/* ================== Sort By Department End =========================== */  
				
				
				$response_identical = array_values(array_unique($response_identical,SORT_REGULAR));
				$response_deptsame = array_values(array_unique($response_deptsame,SORT_REGULAR));
				$response_deptother = array_values(array_unique($response_deptother,SORT_REGULAR)); // cat same
				$response_catother = array_values(array_unique($response_catother,SORT_REGULAR)); 
				$response_nmatch = array_values(array_unique($response_nmatch,SORT_REGULAR));
				
				
				/* ================= User View Count Matching Start ========================== */
				
				$remainarr = array_values(array_merge($response_catother,$response_nmatch));
				$response_sku_str = '';
				$sku_array = [];
				
				if(isset($remainarr)){
					foreach ($remainarr as $pr) {  
					  $response_sku_str = $response_sku_str.",".$pr->product_sku;
					   
					}
					$response_sku_str = ltrim($response_sku_str, ',');
					$sku_array = explode(",",$response_sku_str);
					
					$product_rows1 = DB::table('user_views') 
					->whereIn('user_views.product_sku', $sku_array)  
					->join('master_data', 'user_views.product_sku', '=', 'master_data.product_sku')		
					->join('master_brands', 'master_brands.value', '=', 'master_data.brand')						
					->select(array('master_data.id','master_data.product_description','master_data.product_status','master_data.product_name','master_data.product_sku','master_brands.name as brand_name','master_data.price','master_data.was_price','master_data.main_product_images as image','master_data.LS_ID',DB::raw('count(user_views.user_id) as viewers')))//,'user_views.updated_at as last_visit','user_views.num_views as visit_count'
					->groupBy('user_views.product_sku')
					->orderBy(\DB::raw('count(user_views.user_id)'), 'DESC')
					->get();
					
					$response_nmatch = [];
					foreach ($product_rows1 as $pr) {  
					 $pr->image =  env('APP_URL').$pr->image; 
					  array_push($response_nmatch,$pr);
					  
					}
					
					 
				}
				
				
				/* ================= User View Count Matching End ========================== */
				
				$response = array_values(array_merge($response_identical, $response_deptsame, $response_deptother,  $response_nmatch));
				$response = array_slice($response,0,30);
				return $response;
	}
	
 

	public static function get_product_for_four_digit($product_rows,$LSID){
		
	/*	$product_rows=array (
  0 => 
  array (
    'id' => 673,
    'serial' => 29,
    'product_status' => 'active',
    'product_name' => 'Stone Table Rectangle 95"',
    'product_sku' => '479397',
    'LS_ID' => '1126',
  ),
  1 => 
  array (
    'id' => 701,
    'serial' => 51,
    'product_status' => 'active',
    'product_name' => 'Harper Brass Dining Table with Glass Top',
    'product_sku' => '359011',
    'LS_ID' => '507',
  ),
  2 => 
  array (
    'id' => 1073,
    'serial' => 20,
    'product_status' => 'active',
    'product_name' => 'Harper White Dining Table with Black Marble Top',
    'product_sku' => '580101',
    'LS_ID' => '1123',
  ),
  3 => 
  array (
    'id' => 1111,
    'serial' => 13,
    'product_status' => 'active',
    'product_name' => 'Babylon Round Small Table',
    'product_sku' => '584087',
    'LS_ID' => '1123,407',
  ),
);*/
		 
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
		
		foreach($product_rows as $pr)
		{
			 $pr->image =  env('APP_URL').$pr->image; 
			 $LS_ID_arr = explode(',',$pr->LS_ID); 
			 //$LS_ID_arr = explode(',',$pr['LS_ID']); 
		 
					 
			 if(count($LS_ID_arr)==1)
			{ 
				if($LS_ID_arr[0]==$LSID){
					array_push($response_identical,$pr); 
				}
				else{
					array_push($response_catother,$pr);
				} 
			}
			
			else {  
			
					if(in_array($LSID,$LS_ID_arr))		
					{
						array_push($response_match1,$pr);
					}
					else{
						   array_push($response_catother,$pr);
					}
				
			}
					
					
					
		} 
		$response_identical = array_merge($response_identical,$response_match1);
				
				
				
				
				
				
		/* if(in_array($LSID, $LS_ID_arr)){	
				array_push($response_identical,$pr);
			}
			else{
					array_push($response_catother,$pr);
			}
			*/
		 
		
		$LSID_dept = $LSID[0].$LSID[1].$LSID[2];
		
		
	/* ================== Sort By Department Start =========================== */  
	
		foreach($response_catother as $dept){
			$flag=0;
			$LS_ID_arr = explode(",",$dept->LS_ID);
			//$LS_ID_arr = explode(",",$dept['LS_ID']);
			
			for($i=0;$i<count($LS_ID_arr);$i++){ 
				if ((substr($LS_ID_arr[$i], 0, 3))==  $LSID_dept){  
					$flag=1;
				 	break;
					
				}
				else{ 
						$flag=0;
						
				}
				 
			}
			 
			if($flag==1){
				array_push($response_deptsame,$dept);
			}
			else{
				array_push($response_deptother,$dept); 
			}
			
		
		} 
		
		/* ================== Sort By Department End =========================== */  
		
	/*	$response_identical = array_values(array_unique($response_identical,SORT_REGULAR));
		$response_deptsame = array_values(array_unique($response_deptsame,SORT_REGULAR));
		$response_deptother = array_values(array_unique($response_deptother,SORT_REGULAR)); // cat same 
		
		*/
		
		
		/* ================= User View Count Matching Start ========================== */
				 
				/*$response_sku_str = '';
				$sku_array = [];
				
				if(isset($response_deptother)){
					foreach ($response_deptother as $pr) {  
					  $response_sku_str = $response_sku_str.",".$pr->product_sku;
					//   $response_sku_str = $response_sku_str.",".$pr['product_sku'];
					}
					$response_sku_str = ltrim($response_sku_str, ',');
					$sku_array = explode(",",$response_sku_str);
					
					$product_rows1 = DB::table('user_views') 
					->whereIn('user_views.product_sku', $sku_array)  
					->join('master_data', 'user_views.product_sku', '=', 'master_data.product_sku')	
					->join('master_brands', 'master_brands.value', '=', 'master_data.brand')						
					->select(array('master_data.id','master_data.product_description','master_data.product_status','master_data.product_name','master_data.product_sku','master_brands.name as brand_name','master_data.price','master_data.was_price','master_data.main_product_images as image','master_data.LS_ID',DB::raw('count(user_views.user_id) as viewers')	))//'user_views.updated_at as last_visit','user_views.num_views as visit_count'
					->groupBy('user_views.product_sku')
					->orderBy(\DB::raw('count(user_views.user_id)'), 'DESC')
					->get();
					
					$response_nmatch = [];
					foreach ($product_rows1 as $pr) {  
					 $pr->image =  env('APP_URL').$pr->image; 
					  array_push($response_nmatch,$pr);
					  
					}
					
					 
				}*/
				
				
		/* ================= User View Count Matching End ========================== */
		
	
		//$response = array_values(array_merge($response_identical, $response_deptsame, $response_nmatch));
		$response = array_values(array_merge($response_identical, $response_deptsame, $response_deptother));
		$response = array_slice($response,0,30);
				
		return $response;
	}
	
 


};
