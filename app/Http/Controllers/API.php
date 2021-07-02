<?php

namespace App\Http\Controllers;

use App\Models\BlowoutDeals;
use App\Models\Product;
use App\Models\Variations;
use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Models\Brands;
use App\Models\Subscribe;
use App\Models\Department;
use App\Models\User;
use App\Models\Cart;
use App\Models\Collections;
use App\Models\Deals;
use App\Models\Dimension;
use App\Models\Inventory;
use App\Models\Mailer;
use App\Models\Trending;
use App\Models\UserVisits;
use App\Models\Review;
use App\Models\Utility;
use Auth;
use Illuminate\Support\Facades\Validator;
use Subscribe as GlobalSubscribe;
use App\Models\Order;
use App\Models\ProductCategory; 
use App\Models\Testimonials;

class API extends Controller
{
    public function index(Request $requestuest)
    {
        return false;
    }

    public function get_user()
    {
        return [
            "auth" => [
                "is_user" => Auth::check(),
                "id" => Auth::check() ? Auth::user()->id : null,
                "user" => Auth::user()

            ]
        ];
    }

    public function user_details(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'username' => 'required|alpha_dash'
        ]);

        if ($validator->fails())
            return response()->json(['error' => $validator->errors()], 422);

        $user = User::where('username', $data['username'])->first();

        return $user == NULL ? [null] : $user;
    }

    public function login_user()
    {
        return User::login();
    }
    public function getProducts($dept, $cat = null)
    {
        return Product::get_department_products($dept, $cat);
    }

    public function filter_products($dept, $cat = null, $subCat = null)
    {
        return Product::get_filter_products($dept, $cat, $subCat);
    }

    public function get_product_details(Request $request, $sku)
    {
        $skus = $request->input('skus');
       
        if (isset($skus) && strlen($skus) > 0)
            return Product::get_selected_products(explode(",", $skus));

        return Product::get_product_details($sku);
    }

    public function get_product_variations($sku)
    {
        return Variations::get_variations($sku);
    }

    public function get_swatch_filter($sku)
    {
        return Variations::get_swatch_filter($sku);
    }

    public function mark_favourite($sku)
    {
        return Wishlist::mark_favourite_product($sku);
    }

    public function unmark_favourite($sku)
    {
        return Wishlist::unmark_favourite_product($sku);
    }

    public function get_wishlist(Request $request)
    {
        $board_view = $request->input('board-view');
        if (isset($board_view) && strtolower($board_view) == 'true')
            $board_view = true;
        else $board_view = false;

        return Wishlist::get_whishlist($board_view);
    }

    public function get_all_brands($key = null)
    {
        return Brands::get_all($key);
    }

    public function subscribe_user()
    {
        return Subscribe::subscribe_user();
    }

    public function get_categories($dept_url_name)
    {
        return Department::get_single_department($dept_url_name);
    }

    public function get_banners()
    {
        return Brands::get_banners();
    }

    public function add_to_cart(Request $request)
    {

        if (
            strlen($request->input('product_sku')) > 0
            && strlen($request->input('count')) > 0
        ) {

            $sku = $request->input('product_sku');
            $parent = $request->input('parent_sku');
            $origin = $request->input('cart_origin');
            $origin = strlen($origin) > 0 ? $origin : NULL;

            if (!isset($parent) || strlen($parent) == 0)
                $parent = $sku;

            $count = $request->input('count');

            return Cart::add($sku, $count, $parent, $origin);
        }

        return [
            false
        ];
    }

    public function remove_from_cart(Request $request)
    {
        if (
            strlen($request->input('product_sku')) > 0
            && strlen($request->input('count')) > 0
        ) {

            $sku = $request->input('product_sku');
            $count = $request->input('count');

            return Cart::remove($sku, $count);
        }

        return [
            false
        ];
    }

    public function get_cart(Request $request)
    {
        $data = $request->all();
        $state_code = null;
        $promo_code = null;

        if (
            isset($data['state_code'])
            && strlen($data['state_code']) > 0
        )
            $state_code = strtoupper($data['state_code']);

        if (
            isset($data['promo'])
            && strlen($data['promo']) > 0
        )
            $promo_code = $data['promo'];


        return Cart::cart($state_code, $promo_code);
    }

    public function get_inventory()
    {
        return Inventory::get();
    }

    public function get_collection_details(Request $request)
    {
        $collection_key = $request->input('collection');
        if (!isset($collection_key) || strlen($collection_key) == 0)
            return [];

        return Collections::get_collection_details($collection_key);
    }

    public function get_all_collections()
    {

        return Collections::get_collection_list();
    }

    public function get_deals()
    {

        return Deals::get_deals();
    }

    public function get_visited_skus()
    {

        if (Auth::check()) {
            return UserVisits::get_visited_skus(Auth::user()->id);
        }

        return [
            false
        ];
    }

    public function get_blowout_deals()
    {
        return BlowoutDeals::get_deals();
    }

    public function get_trending_products()
    {
        return Trending::get_trending();
    }
	
 
	

	public function save_product_review(Request $request) { 
        $data = $request->all(); 
		return Review::save_product_review($data);
            
    }
	
	public function get_product_review($sku,$limit=6) {
            $sku = str_replace('getreview-','',$sku);
            return Review::get_product_review($sku,$limit);
            
    }
    
	public function get_all_review($sku)
    {
        return Review::get_all_review($sku);
    }
	
	public function mark_helpful_review(Request $request)
    {
		$data = $request->all();
        return Review::mark_helpful_review($data);
    }
	
	public function mark_reported_review(Request $request)
    {
		$data = $request->all();
        return Review::mark_reported_review($data);
    }

	public function get_userproduct_list($sku) {
            $sku = str_replace('getreview-','',$sku);
            return Product::get_userproduct_list($sku);
            
    }
	 
	
	public function get_order_status() {

		return Order::get_order_status();
            
    }
	public function save_email_checkout(Request $request)
    {
		$data = $request->all();
        return Cart::save_email_checkout($data);
    }
	
	public function get_all_collection_with_count() { 
		return Collections::get_all_collection_with_count();
            
    }
	

	public function save_collection(Request $request)
    {
		$data = $request->all();
        return Collections::save_collection($data);
    }
	
	public function get_dept_list()
    {
        return ProductCategory::get_dept_list();
    }
	
	public function get_cat_list($deptname)
    {
        return ProductCategory::get_cat_list($deptname);
    }
	

	public function get_subcat_list($catname,$deptname)
    {
        return ProductCategory::get_subcat_list($catname,$deptname);
    }

    public function trigger_mailer(Request $request) {
        $data = $request->all();
        return Mailer::trigger_mail($data);
    }

    public function get_testimonials()
    {
        return Testimonials::get_testimonials();
    }
}
