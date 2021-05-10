<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Auth;
use Illuminate\Support\Facades\Config;

class Cart extends Model
{

    protected $table = "lz_user_cart";
    private static $cart_table = "lz_user_cart";
    private static $shipment_code_table = 'lz_ship_code';
    private static $inventory_table = 'lz_inventory';

    public static function add($sku, $count, $parent, $origin = null)
    {
        // add the product in the cart, 
        // don't delete the product from the inventory 
        // do that on payment initiation

        $is_guest = 0;


        if (Auth::check()) {
            $user_id = Auth::user()->id;
        } else {
            $user_id = 'guest-1';
            $is_guest = 1;
        }



        // let's get the items that are already present in the user_cart 
        // right now and then we'll only insert new products if total item count
        // suits the inventory count
        $items_in_cart = DB::table(Cart::$cart_table)
            ->where('user_id', $user_id)
            ->where('product_sku', $sku)
            ->where('is_active', 1)
            ->get()->count();

        $items_in_inventory = DB::table(Cart::$inventory_table)
            ->select(['quantity as count'])
            ->where('product_sku', $sku)
            ->get();

        if (isset($items_in_inventory[0])) {
            $items_in_inventory_count = $items_in_inventory[0]->count;

            $to_insert = $count;
            $inserted = 0;

            if (($items_in_cart + $to_insert) > $items_in_inventory_count) {
                return [
                    'status' => false,
                    'msg' => 'Currently available stock already added to cart'
                ];
            }


            while ($count--) {
                $is_inserted = DB::table(Cart::$cart_table)
                    ->insert([
                        'user_id' => $user_id,
                        'product_sku' => $sku,
                        'parent_sku' => $parent,
                        'is_guest' => $is_guest,
                        'origin' => $origin
                    ]);

                if ($is_inserted)
                    $inserted++;
            }


            // update parent skus for same order skus 
            // this will take care of the case where we have same variations 
            // SKUs for different parents

            $update = DB::table(Cart::$cart_table)
                ->where('product_sku', $sku)
                ->update(['parent_sku' => $parent]);

            if ($to_insert == $inserted) {
                return [
                    'status' => true,
                    'msg' => 'product ' . $sku . ' added to cart'
                ];
            }
        }

        return [
            'status' => false,
            'msg' => 'could not add product ' . $sku . ' to cart'
        ];
    }

    public static function remove($sku, $count)
    {

        if (Auth::check()) {
            $user_id = Auth::user()->id;
        } else {
            $user_id = 'guest-1';
        }


        $is_updated = DB::table(Cart::$cart_table)
            ->where("product_sku", $sku)
            ->where("user_id", $user_id)
            ->where("is_active", 1)
            ->take($count)->update(['is_active' => '0']);


        if ($is_updated)
            return [
                'status' => true,
                'msg' => 'product ' . $sku . ' removed from cart'
            ];

        return [
            'status' => false,
            'msg' => 'could not remove product ' . $sku . ' from cart'
        ];
    }

    private function cut($code)
    {
        return substr($code, 0, 2);
    }

    private function end($code)
    {
        return substr($code, 2, strlen($code) - 2);
    }

    public static function cart($state = null, $promo_code = null)
    {
        $variation_tables = Config::get('tables.variations');
        $native_shipping_codes = Config::get('shipping.native_shipping_codes');
        $user_email = '';
         


        if (Auth::check()) {
            $user_id = Auth::user()->id;
            $user_email = Auth::user()->email;
        } else {
            $user_id = 'guest-1';
        }

        // calculating shipment cost for each SKU
        $rows_shipment_code = DB::table(Cart::$shipment_code_table)
            ->get()
            ->toArray();

        // make haskmap that will store the comibation of code and brand from 
        // normal shipping codes and just shipping code to value mapping 
        // for native shipping codes that include free shipping and LZ shipping

        $shipment_codes = [];
        foreach ($rows_shipment_code as $row) {

            if (strlen($row->code) > 2)
                $shipment_codes[$row->code] = [
                    'single' => $row->rate_single,
                    'multi' => $row->rate_multi
                ];
        }

        // we can have products that are not in the master_data table 
        // but present in one of the variations table, so for those products 
        // we'll have to make a separate list

        // get all the products for this user 
        $rows = Cart::where('user_id', $user_id)
            ->where('is_active', 1)->get();
        $parents = []; //parent[i] => variations[i]
        $variations = [];

        foreach ($rows as &$row) {
            // we will only process products that are variations of the parent 
            // product, for normal products (parent products) the code after this 
            // loop will be applied

            if (
                isset($row->product_sku) && isset($row->parent_sku)
                && strlen($row->parent_sku) > 0 && $row->product_sku != $row->parent_sku
            ) {
                // for variations SKU, details of parent SKU from master_data table
                // [parent_sku] => [variation_sku1, var_sku2...]
                if (!isset($parents[$row->parent_sku]))
                    $parents[$row->parent_sku] = [];

                if (!in_array($row->product_sku, $parents[$row->parent_sku]))
                    $parents[$row->parent_sku][] = $row->product_sku;
            }
        }

        /*echo json_encode($parents);
        echo json_encode($variations);
        die();*/

        // getting all distinct parents
        $dist_parents = [];
        foreach ($parents as $parent_sku => $variation_skus)
            $dist_parents[] = $parent_sku;

        // get parent details
        $parent_rows = DB::table('master_data')
            ->select([
                "product_name",
                "product_sku",
                "site_name",
                "reviews",
                "rating",
                "mfg_country",
                "product_description",
                "master_brands.value as site_value",
                "master_brands.name as site",

                // availability related data
                "is_back_order",
                "back_order_msg",
                "back_order_msg_date",
                "online_msg"
            ])
            ->whereIn('master_data.product_sku', $dist_parents)
            ->join("master_brands", "master_data.site_name", "=", "master_brands.value")
            ->get();
        $parent_index = 0;

        $cart = [];
       
        foreach ($parent_rows as $row) {
            // for each parent get the Product Name and Site Name
            // from Site Name we'll be deciding the variations table
            // for that variation SKU 

            $table = isset($variation_tables[$row->site_name]['table']) ? $variation_tables[$row->site_name]['table'] : null;
            $name = isset($variation_tables[$row->site_name]['table']) ? $variation_tables[$row->site_name]['name'] : null;
            $image = isset($variation_tables[$row->site_name]['table']) ? 'image_path' : null;
            $sku = isset($variation_tables[$row->site_name]['table']) ? 'sku' : null;    //$variation_tables[$row->site_name]['sku']
            $parent_sku_field = isset($variation_tables[$row->site_name]['table']) ? $variation_tables[$row->site_name]['parent_sku'] : null;
            // get variations details, we only need name and image

            if (isset($table) && isset($name) && isset($image)) {
                $vrows = DB::table($table)
                    ->select([
                        $table . "." . $sku . ' as product_sku',
                        $table . ".attribute_1",
                        DB::raw('count(*) as count'),
                        DB::raw('concat("https://www.lazysuzy.com", ' . $image . ') as image'),
                        //$name . ' as product_name',
                        'lz_inventory.price as retail_price',
                        'lz_inventory.ship_code',
                        'lz_inventory.ship_custom',
                        'lz_inventory.quantity as max_available_count',
                        'lz_inventory.price',
                        'lz_inventory.was_price',
                        'lz_ship_code.label',

                    ])->whereIn($sku, $parents[$row->product_sku]) // get all variations related to this parent product_sku
                    ->join("lz_inventory", $table . "." . $sku, "=", "lz_inventory.product_sku")
                    ->join(Cart::$cart_table, Cart::$cart_table . ".product_sku", "=", "lz_inventory.product_sku")
                    ->join("lz_ship_code", "lz_ship_code.code", "=", "lz_inventory.ship_code")
                    ->where(Cart::$cart_table . '.user_id', $user_id)
                    ->where(Cart::$cart_table . '.is_active', 1)
                    ->where($table . '.' . $parent_sku_field, $row->product_sku) // where parent SKU is given in variations table
                    //->where ($table . '.has_parent_sku',1)
                    ->groupBy(Cart::$cart_table . '.product_sku');
 
                // $vrows = $vrows->toSql();return $vrows;
                $vrows = $vrows->get()->toArray();

                // one parent SKU can have many variations SKUs 
                // in the cart
                // if you need to add any new info from master table to cart API do it 
                // here and in one more place in the below section 

                foreach ($vrows as &$vrow) {
                    $image_rows = DB::table('master_data')
                        ->select([
                            "main_product_images"
                        ])
                        ->where('master_data.product_sku', $vrow->product_sku)->get();

                    $nm = $row->product_name;
                    if (isset($vrow->attribute_1) && $vrow->attribute_1 != 'null') {
                        $str_exp1 = explode(":", $vrow->attribute_1);
                        if (isset($str_exp1[0]) && isset($str_exp1[1])) {
                            $nm = $nm . ' ' . $str_exp1[1];
                        }
                    }

                    /*if(isset($vrow->attribute_2) && $vrow->attribute_2!='null'){
					   $str_exp2 = explode(":", $vrow->attribute_2); 
                        if (isset($str_exp2[0]) && isset($str_exp2[1])) {
							$nm = $nm.' '.$str_exp2[1];
						}
					}
					
					if(isset($vrow->attribute_3) && $vrow->attribute_3!='null'){
					   $str_exp3 = explode(":", $vrow->attribute_3);
                        if (isset($str_exp3[0]) && isset($str_exp3[1])) {
							$nm = $nm.' '.$str_exp3[1];
						}
					}
					
					if(isset($vrow->attribute_4) && $vrow->attribute_4!='null'){
					   $str_exp4 = explode(":", $vrow->attribute_4);
                        if (isset($str_exp4[0]) && isset($str_exp4[1])) {
							$nm = $nm.' '.$str_exp4[1];
						}
					}
					
					if(isset($vrow->attribute_5) && $vrow->attribute_5!='null'){
					   $str_exp5 = explode(":", $vrow->attribute_1);
                        if (isset($str_exp5[0]) && isset($str_exp5[1])) {
							$nm = $nm.' '.$str_exp5[1];
						}
					}
					
					if(isset($vrow->attribute_6) && $vrow->attribute_6!='null'){
					   $str_exp6 = explode(":", $vrow->attribute_1);
                        if (isset($str_exp6[0]) && isset($str_exp6[1])) {
							$nm = $nm.' '.$str_exp6[1];
						}
					}*/
                    $imgnm = '';
                    if ($vrow->image != null) {

                        $imgarr = preg_split("/,/", $vrow->image);
                        $imgnm = $imgarr[0];
                    } else {
                        $imgnm = 'https://www.lazysuzy.com' . $image_rows[0]->main_product_images;
                    }
                    $vrow->parent_sku = $row->product_sku;
                    $vrow->parent_name = $nm; //$row->product_name;
                    $vrow->product_name = $row->product_name;
                    $vrow->review = $row->reviews;
                    $vrow->rating = $row->rating;
                    $vrow->description = $row->product_description;
                    $vrow->site = $row->site;
                    $vrow->brand_id = $row->site_name;
                    $vrow->mfg_county = $row->mfg_country;
                    $vrow->image = $imgnm;
                    $vrow->is_back_order = $row->is_back_order;
                    $vrow->back_order_msg = $row->back_order_msg;
                    $vrow->back_order_msg_date = $row->back_order_msg_date;
                    $vrow->online_msg = $row->online_msg;
                    $cart[] = $vrow;
                }
            }
        }

        // if you need to add data from master table to card API output 
        // do it in this section
        $rows = DB::table(Cart::$cart_table)
            ->select(
                Cart::$cart_table . '.product_sku',
                Cart::$cart_table . '.origin as product_origin',

                DB::raw('count(*) as count'),
                'master_data.product_name',
                'lz_inventory.price as retail_price',
                'lz_inventory.ship_code',
                'lz_inventory.ship_custom',
                'lz_inventory.quantity as max_available_count',
                'lz_inventory.price',
                'lz_inventory.was_price',
                DB::raw('concat("https://www.lazysuzy.com", master_data.main_product_images) as image'),
                'master_data.product_description',
                'master_data.reviews',
                'master_data.rating',
                'master_data.mfg_country',

                'master_data.is_back_order',
                'master_data.back_order_msg',
                'master_data.back_order_msg_date',
                'master_data.online_msg',

                'master_brands.name as site',
                'master_brands.value as brand_id',
                'lz_ship_code.label'
            )
            ->join("master_data", "master_data.product_sku", "=", Cart::$cart_table . ".product_sku")
            ->join("lz_inventory", "lz_inventory.product_sku", "=", "master_data.product_sku")
            ->join("master_brands", "master_data.site_name", "=", "master_brands.value")
            ->join("lz_ship_code", "lz_ship_code.code", "=", "lz_inventory.ship_code")

            ->where(Cart::$cart_table . '.user_id', $user_id)
            ->where(Cart::$cart_table . '.is_active', 1)

            ->groupBy([Cart::$cart_table . '.user_id', Cart::$cart_table . '.product_sku']);

        $rows = $rows->get()->toArray();

        //$cart_rows = array_merge($rows, $cart);
        $cart_rows = $cart;
        foreach ($rows as $parent_product) {
            $parent_sku = $parent_product->product_sku;
            $parent_sku_found = false;
            foreach ($cart_rows as $in_cart_variation) {
                if ($in_cart_variation->product_sku == $parent_sku) {
                    $parent_sku_found = true;
                    break;
                }
            }

            if ($parent_sku_found == false) {
                $cart_rows[] = $parent_product;
            }
        }

        $products = [];

        // [brand] => [total_price of products for that brand]
        $total_cost_rate_shipping = [];

        // [brand] => [total_cost for barnd]
        $total_cart_fixed_shipping = [];
        foreach ($cart_rows as $row => &$product) {

            $p_val = $wp_val = $discount = null;

            if (!isset($product->was_price))
                $product->was_price = $product->price;

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

            if (is_numeric($p_val) && is_numeric($wp_val) && $wp_val > 0) {
                $discount = (1 - ($p_val / $wp_val)) * 100;
                $discount = number_format((float) $discount, 2, '.', '');
            }

            $product->discount = $discount;
            $product->total_price = $product->price * $product->count;

            // $product->ship_custom already has a value because we joined the tables
            // we're now updating this value to match correct shipping cost 

            // set correct shipping cost
            $ship_code = (new self)->cut(($product->ship_code));
            //echo json_encode($shipment_codes);die();
            if ($ship_code == config('shipping.lazysuzy_shipping')) {

                //$product->ship_custom = $shipment_codes[$product->ship_code];

            } else if ($ship_code == config('shipping.free_shipping')) {

                $product->ship_custom = 0;
            } else if ($ship_code == config('shipping.fixed_shipping')) {

                $product->ship_custom = 0;
                $product->is_calculated_separately = true;

                if (!isset($total_cart_fixed_shipping[$product->brand_id]))
                    $total_cart_fixed_shipping[$product->brand_id] = 0;

                $total_cart_fixed_shipping[$product->brand_id] = $shipment_codes[$product->ship_code];
            } else if ($ship_code == config('shipping.rate_shipping')) {

                $product->ship_custom = 0;
                $product->is_calculated_separately = true;
                if (!isset($total_cost_rate_shipping[$product->brand_id]))
                    $total_cost_rate_shipping[$product->brand_id] = 0;

                $total_cost_rate_shipping[$product->brand_id] += ($product->retail_price * $product->count);
            }

            $product->total_ship_custom = $product->ship_custom * $product->count;
        }

        // set priority 
        // if WG product is there in the cart for that brand 
        // just consider WG cost, don't consider SG/SC costs
        foreach ($cart_rows as $row => &$product) {

            foreach ($total_cart_fixed_shipping as $brand => $val) {
                if ($product->brand_id == $brand) {
                    $product->total_ship_custom = 0;
                    $product->is_calculated_separately = true;
                }
            }
        }

        // if $state is not null, get the state tax and add it in the total
        // item cost
        $sales_tax = 0; // this is applied 1 time for a order.
        $sales_shipping = false; // if false, apply tax on only product price else apply on product price and shipping
        if (isset($state)) {
            $sales_t = SalesTax::get_sales_tax($state);
            $sales_tax = $sales_t[0];
            $sales_shipping = $sales_t[1];
        }


        $res = ['user' => ['emailid' => $user_email], 'products' => [], 'order' => [
            'sub_total' => 0,
            'total_cost' => 0,
            'shipment_total' => 0,
            'sales_tax_total' => 0
        ]];

        foreach ($cart_rows as $p) {
            $res['products'][] = $p;
            $res['order']['sub_total'] += $p->total_price;
            $res['order']['shipment_total'] += $p->total_ship_custom;
        }

        // now add the SV and WG shipment product rate
        $total_fixed_shipping_charge_for_all_brands = 0;

        // if there is only 1 brand products, else if there are more than 1 brand 
        // we need to apply the multi rate for the shippment 1 time in the order.
        if (sizeof($total_cart_fixed_shipping) == 1) {
            foreach ($total_cart_fixed_shipping as $brand => $value) {
                $total_fixed_shipping_charge_for_all_brands += $value['single'];
                break;
            }
        } else if (sizeof($total_cart_fixed_shipping) > 1) {
            $max_fixed_ship_price = 0;
            foreach ($total_cart_fixed_shipping as $brand => $value) {
                if ($max_fixed_ship_price < $value['multi']) {
                    $max_fixed_ship_price = $value['multi'];
                }
            }

            $total_fixed_shipping_charge_for_all_brands = $max_fixed_ship_price;
        }

        $total_rate_shipping_charge_for_all_brands = 0;
        if (sizeof($total_cost_rate_shipping) > 0) {
            foreach ($total_cost_rate_shipping as $brand => $price) {
                $total_rate_shipping_charge_for_all_brands += ($price * $shipment_codes[config('shipping.rate_shipping') . $brand]['single']);
            }
        }

        $res['order']['shipment_total'] += $total_fixed_shipping_charge_for_all_brands + $total_rate_shipping_charge_for_all_brands;
        if ($sales_shipping)
            $res['order']['sales_tax_total'] = ($res['order']['sub_total'] + $res['order']['shipment_total']) * $sales_tax;
        else
            $res['order']['sales_tax_total'] = $res['order']['sub_total'] * $sales_tax;


        $res['order']['total_cost'] = $res['order']['shipment_total']
            + $res['order']['sales_tax_total']
            + $res['order']['sub_total'];


        $res['order']['total_promo_discount'] = 0;
        $res['order']['original_total_cost'] = round($res['order']['total_cost'], 2);
        $res['order']['original_sub_total'] = round($res['order']['sub_total'], 2);

        if (isset($promo_code))
            $res = PromoDiscount::calculate_discount($res, $promo_code);

        //return $res;

        /********************************************************************************** */
        // again calculate sales tax because we need sales tax to be calculated 
        // on discounted values 

        if ($sales_shipping)
            $res['order']['sales_tax_total'] = ($res['order']['sub_total'] + $res['order']['shipment_total']) * $sales_tax;
        else
            $res['order']['sales_tax_total'] = $res['order']['sub_total'] * $sales_tax;

        $res['order']['total_cost'] = $res['order']['shipment_total']
            + $res['order']['sales_tax_total']
            + $res['order']['sub_total'];

        /******************************************************************************** */
        $res['order']['sales_tax_total'] = round((float) $res['order']['sales_tax_total'], 2);
        $res['order']['sub_total'] = round((float) $res['order']['sub_total'], 2);
        $res['order']['shipment_total'] = round((float) $res['order']['shipment_total'], 2);
        $res['order']['total_cost'] = round((float) $res['order']['total_cost'], 2);

        return $res;
    }

    public static function save_email_checkout($data)
    {
        $a['msg'] = 'Successfully updated';
        $a['status'] = 1;
        if (Auth::check()) {
            $user_id = Auth::user()->id;
        } else {
            $user_id = 0;
        }

        $emailid  = $data['emailid'];
        $is_updated = DB::table('lz_user_cart')
            ->where("user_id", $user_id)
            ->where("is_active", 1)
            ->update(['email' => $emailid]);
        return     $a;
    }

}
