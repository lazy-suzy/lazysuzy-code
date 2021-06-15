<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Auth;

use function PHPSTORM_META\map;

class PromoDiscount extends Model
{
    protected $table = "lz_promo";

    /**
     * Calculates total cart cost in case use has applied a promo code.
     *
     * @param [type] $cart
     * @param [type] $promo_code
     * @return void
     */
    public static function calculate_discount($cart, $promo_code)
    {

        $user = Auth::user(); 
        // first check if the promo code is valid or not.
        // fast fail system.
        $promo_status = self::check_promo_code($user, $cart, $promo_code);  
        if (!$promo_status['is_valid']) {
            $cart['promo_details'] = $promo_status['details'];
            return $cart;
        }
 
		
		$total_dicount_availed = 0;

        $promo_details = $promo_status['details']; 
		if($promo_details['discount_details']['is_SKU_specific']==1){
			
				$in_cart_skus = [];
				foreach ($cart['products'] as $product) {
					$in_cart_skus[] = $product->product_sku;
				} 
				  
			
				$sql = DB::table('lz_inventory') 
				->select('product_sku', 'parent_sku') 
				->where('promo_id', '=', $promo_details['discount_details']['id']) 
				->whereIn('product_sku', $in_cart_skus )
				->get();
				
				
				if($sql=='[]'){
					$cart['promo_details']['error_msg'] = "Sorry! This coupon is not applicable on any product in your cart";
					return $cart;
				}else{
					    $arr = [];
						foreach($sql as $data){
							array_push($arr,$data->product_sku);
						}
						$cart = self::add_promo_discount($arr, $cart, $promo_details['discount_details']);//return $cart;
					   
				}
				
			 
			
		}
		else{ 
                 
                
                $valid_SKUs_for_discount = self::LSIDs_allowed($cart, $promo_details['discount_details']); 
                if (sizeof($valid_SKUs_for_discount) == 0) { 
                $cart['promo_details']['error_msg'] = "Sorry! This coupon is not applicable on any product in your cart";
                return $cart;
                } else {

                // check if promo applies on the whole order or on individual products
                $promo_apply = $promo_details['discount_details']['apply_on'];
                if ($promo_apply == Config::get('meta.discount_on_products')) {  
                    $cart = self::add_promo_discount($valid_SKUs_for_discount, $cart, $promo_details['discount_details']); return $cart;
                } else {
                    // if promo is to be applied on total order
                    // then we just substract the discount amount from the total_cost 
                    $total_dicount_availed = self::apply_discount_on_total($cart, $promo_details['discount_details']); 
                }
                
            }
            
				 
		
		}

        


        // every product will have promo discount in it's object 
        // ['order'] object still has total price so reduce the price from the 
        // orders object data here

        
        foreach ($cart['products'] as $product) {
            $total_dicount_availed += isset($product->promo_discount) ? $product->promo_discount : 0;
        }
         

        $cart['order']['sub_total'] = max(0, $cart['order']['sub_total'] - $total_dicount_availed);
        $cart['order']['total_cost'] = $cart['order']['shipment_total']
            + $cart['order']['sales_tax_total']
            + $cart['order']['sub_total'];
        
        $cart['order']['total_promo_discount'] = round(($cart['order']['original_total_cost'] - $cart['order']['total_cost']),2); //$total_dicount_availed;
 
        $cart['promo_details'] = [
            'code' => $promo_code,
            'name' => $promo_details['discount_details']['name'],
            'description' => $promo_details['discount_details']['description'],
            'total_discount' => round(($cart['order']['original_total_cost'] - $cart['order']['total_cost']),2)// $total_dicount_availed
        ];

        return $cart;
    }

    private static function apply_discount_on_total($cart, $promo_details)
    {

        $promo_type = $promo_details['type'];
        $total_product_price_before_discount = $cart['order']['sub_total'];
        $promo_discount = 0;
        $promo_discount_value = round((float)$promo_details['value'], 2);
        if ($promo_type == Config::get('meta.discount_percent')) {
            $promo_discount = $total_product_price_before_discount * ((float) $promo_details['value'] / 100);
        } else if ($promo_type == Config::get('meta.discount_flat')) {
            $promo_discount = $promo_discount_value;
        }

        if ($total_product_price_before_discount - $promo_discount_value <= 0)
            $promo_discount = $total_product_price_before_discount;
        return round($promo_discount, 2);
    }

    
	private static function add_promo_discount($applicable_SKUs, $cart, $promo_details)
    {   
         //return json_decode($promo_details['value_multi']);
        // check if promo is percentage type or flat type
        $promo_type = $promo_details['type'];
        $total_promo_discount = 0; 
        $promo_discount = 0;
        $totalcost=0;
        $totalpercent = 0;
        $shipcodefixed = '';
        $shipcodeprcnt = '';
 
        $ship_arr = (new self)->unique_multidim_array($cart,'brand_id'); 
        $shipcode_arr = (new self)->unique_multidim_array_shipcode($cart,'ship_code');  

        foreach ($cart['products'] as &$product) {  
            // if this SKU is applicable for promo code
            if (in_array($product->product_sku, $applicable_SKUs)) {  
                $total_product_cost_before_discount = (float)$product->total_price;
                
                if ($promo_type == Config::get('meta.discount_percent')) {
						 	if($promo_details['value']>0 && $promo_details['value']!=null){
								 $promo_discount = $total_product_cost_before_discount * ((float) $promo_details['value'] / 100);
						    }
							else{ 
									$promo_discount = 0;
									  
									foreach(json_decode($promo_details['value_multi']) as $desc_sub){
										if($product->total_price >= $desc_sub->price_limit){
											$promo_discount = $total_product_cost_before_discount * ((float) $desc_sub->discount / 100);
										}
										
									}
								
							}
							 
                   
                } else if ($promo_type == Config::get('meta.discount_flat')) {
							if($promo_details['value']>0 && $promo_details['value']!=null){ 
								 $promo_discount = round((float)$promo_details['value'], 2);
							}else{
									$promo_discount = 0;
									foreach(json_decode($promo_details['value_multi']) as $desc_sub){ 
										if($product->total_price >= $desc_sub->price_limit){
											$promo_discount = round((float)$desc_sub->discount, 2); 
										}
										
									}
							}
							 
                    
                }
                else if($promo_type == Config::get('meta.discount_ship')){
                    if($promo_details['type_ship'] == '*'){
                        if($promo_details['applicable_brands']=='*'){ 
                             $cart['order']['shipment_total'] = 0;
                        }
                        else if($promo_details['applicable_brands']==$product->brand_id){ 
                            $cart['order']['shipment_total'] = $cart['order']['shipment_total']-$product->total_ship_custom;
                       
                            
                            if((substr($product->ship_code,0,2))==config('shipping.rate_shipping')){ // for % as shipping rate
                                $totalpercent = $totalpercent+$product->total_price;
                                $shipcodeprcnt = $product->ship_code;
                           
                            }
                            if((substr($product->ship_code,0,2))==config('shipping.fixed_shipping')){ // for $amount as shipping rate
                                
                                if(count($ship_arr)<=2){ 
                                        $shipcodefixed = $product->ship_code;
                                }
                            }
                        }
                    }
                    else{
                        if($promo_details['applicable_brands']==$product->brand_id && $promo_details['type_ship']==$product->ship_code){
                            
                            if((substr($product->ship_code,0,2))==config('shipping.rate_shipping')){ // for % as shipping rate
                                        $totalpercent = $totalpercent+$product->total_price;
                                        $shipcodeprcnt = $product->ship_code;
                             
                              }
                              else if((substr($product->ship_code,0,2))==config('shipping.fixed_shipping')){ // for $amount as shipping rate
                                    
                                    if(count($ship_arr)<=2){ 
                                        $cart['order']['shipment_total'] = $cart['order']['shipment_total']-$product->total_ship_custom;
                                        $totalcost = $totalcost+$product->total_price;
                                    }
                              }
                             
                            
                        }
                    }
                    
                }
 

                $promo_discount = round($promo_discount, 2);
				if($promo_discount>0){
					$product->is_promo_applied = true;
				}
				else{
						$product->is_promo_applied = false;
				}
                $price_after_discount = max(0, $total_product_cost_before_discount - $promo_discount);
                $product->promo_discount = $price_after_discount == 0 ? ($total_product_cost_before_discount) : $promo_discount;

                $product->total_price = $price_after_discount;
                $product->original_total_price = $total_product_cost_before_discount;

			} else {
				 
                $product->is_promo_applied = false;
            }
        }

       

        if($shipcodeprcnt!=''){
            $get_shipamount = DB::table('lz_ship_code')
            ->select(['rate_single'])
            ->where('code', $shipcodeprcnt)
            ->get();

            $rate = ($totalpercent*$get_shipamount[0]->rate_single);  
            $cart['order']['shipment_total'] = $cart['order']['shipment_total']-round($rate,2);
            
        }
        return '==='.$cart['order']['shipment_total'];
        if($shipcodefixed!=''){
            $get_shipamount = DB::table('lz_ship_code')
            ->select(['rate_single','rate_multi'])
            ->where('code', $shipcodefixed)
            ->get();
            $shiparrcount = count($ship_arr);
            if($shipcodeprcnt!=''){
                $shiparrcount = count($ship_arr)-1;
            }
             
            if($shiparrcount<=2){
                if(count($shipcode_arr['wg'])>1){
                    $rate = round($get_shipamount[0]->rate_multi,2);
                    $getsvcost = $cart['order']['shipment_total']-$rate;
                }
                else{
                        $rate = round($get_shipamount[0]->rate_single,2);
                        $getsvcost = $cart['order']['shipment_total']-$rate;
                }
                if($shiparrcount==2){
                    if (($key = array_search($shipcodefixed, $shipcode_arr['wg'])) !== false) {
                        unset($shipcode_arr['wg'][$key]);
                    }
                    if (($key = array_search($shipcodefixed, $shipcode_arr['sv'])) !== false) {
                        unset($shipcode_arr['sv'][$key]);
                    }
                    $total = count($shipcode_arr['wg'])+count($shipcode_arr['sv']);

                    if(count($shipcode_arr['wg'])>1 || count($shipcode_arr['sv'])>1){ //|| $total>1
                        $rate = round($get_shipamount[0]->rate_multi,2);
                    }
                    else{
                            $rate = round($get_shipamount[0]->rate_single,2);
                    }
                        
                }
                else{
                    $rate = round($get_shipamount[0]->rate_single,2);
                }
               // return '$getsvcost'.$cart['order']['shipment_total'].'===='.$rate;
                $temp = $cart['order']['shipment_total']-$rate;
                $cart['order']['shipment_total'] = $cart['order']['shipment_total']-$temp+$getsvcost ;
            }

            //return count($ship_arr).'---->'.$rate.'===='.$temp."++++".$cart['order']['shipment_total'];
        }

        
		
        if($totalcost>0){
            
            $get_shipamount = DB::table('lz_ship_code')
            ->select(['rate_single','rate_multi'])
            ->where('code', $promo_details['type_ship'])
            ->get();
            
            if((substr($promo_details['type_ship'],0,2))==config('shipping.rate_shipping')){ // for % as shipping rate
                $rate = ($totalcost*$get_shipamount[0]->rate_single);  
                $cart['order']['shipment_total'] = $cart['order']['shipment_total']-round($rate,2);     
            }
            else if((substr($promo_details['type_ship'],0,2))==config('shipping.fixed_shipping')){ // for $amount as shipping rate
                if(count($shipcode_arr['wg'])>1){
                    $rate = round($get_shipamount[0]->rate_multi,2);
                    $getsvcost = $cart['order']['shipment_total']-$rate;
                }
                else{
                        $rate = round($get_shipamount[0]->rate_single,2);
                        $getsvcost = $cart['order']['shipment_total']-$rate;
                }
                if (($key = array_search($promo_details['type_ship'], $shipcode_arr['wg'])) !== false) {
                    unset($shipcode_arr['wg'][$key]);
                }
                if (($key = array_search($promo_details['type_ship'], $shipcode_arr['sv'])) !== false) {
                    unset($shipcode_arr['sv'][$key]);
                }
                
                if(count($shipcode_arr['wg'])>1){
                    $rate = round($get_shipamount[0]->rate_multi,2); 
                }
                else{
                        $rate = round($get_shipamount[0]->rate_single,2); 
                }
                 
                $temp = $cart['order']['shipment_total']-$rate;
                $cart['order']['shipment_total'] = $cart['order']['shipment_total']-$temp+$getsvcost ; 
            }
            
        }
        if($cart['order']['shipment_total'] <0){
            $cart['order']['shipment_total']=0;
        }
        return $cart;
    }

	
	
	private static function add_promo_discount_udated($applicable_SKUs, $cart, $promo_details)
    {  
        // check if promo is percentage type or flat type
        $promo_type = $promo_details['type'];
        $total_promo_discount = 0; 
        foreach ($cart['products'] as &$product) { 
            // if this SKU is applicable for promo code
            if (in_array($product->product_sku, $applicable_SKUs)) {  
                $total_product_cost_before_discount = (float)$product->total_price;
                
                if ($promo_type == Config::get('meta.discount_percent')) {
							if($promo_details['value']>0){
								 $promo_discount = $total_product_cost_before_discount * ((float) $promo_details['value'] / 100);
							}
							else{ 
									$promo_discount = 0;
									if($promo_details['value_multi']!=''){
									  
										foreach(json_decode($promo_details['value_multi']) as $desc_sub){
											if($product->total_price >= $desc_sub->price_limit){
												$promo_discount = $total_product_cost_before_discount * ((float) $desc_sub->discount / 100);
											}
											
										}
									}
							}
                   
                } else if ($promo_type == Config::get('meta.discount_flat')) {
							if($promo_details['value']>0){
								 $promo_discount = round((float)$promo_details['value'], 2);
							}
							else{
									$promo_discount = 0;
									if($promo_details['value_multi']!=''){
									 
										foreach(json_decode($promo_details['value_multi']) as $desc_sub){ 
											if($product->total_price >= $desc_sub->price_limit){
												$promo_discount = round((float)$desc_sub->discount, 2); 
											}
											
										}
									}
							}
                    
                }

                $promo_discount = round($promo_discount, 2);
				if($promo_discount>0){
					$product->is_promo_applied = true;
				}
				else{
						$product->is_promo_applied = false;
				}
                $price_after_discount = max(0, $total_product_cost_before_discount - $promo_discount);
                $product->promo_discount = $price_after_discount == 0 ? ($total_product_cost_before_discount) : $promo_discount;

                $product->total_price = $price_after_discount;
                $product->original_total_price = $total_product_cost_before_discount;

			} else {
				 
                $product->is_promo_applied = false;
            }
        }
		
		/*$allow_count = $promo_details['allowed_count']-1;
		
		$sql = DB::table('lz_promo')
                    ->where('id', $promo_details['id'])
                    ->update(['allowed_count' => $allow_count]);
		*/
        return $cart;
    }

    /**
     * Returns map of product SKU to LS_IDs
     *
     * @param [type] $skus
     * @return Array
     */
    private static function get_product_LSID($skus)
    {
        $rows = DB::table(Config::get('tables.master_table'))
            ->select(['product_sku', 'LS_ID'])
            ->whereIn('product_sku', $skus)
            ->get()->toArray();


        return array_column($rows, 'LS_ID', 'product_sku');
    }

    private static function check_promo_code($user, $cart, $promo_code)
    {

        $status = [];
        $status['is_valid'] = false;
        $status['code'] = $promo_code;
        $status['details'] = [
            'code' => $promo_code,
            'error_msg' => null,
        ];
        if (!isset($user)) {
            $status['details']['error_msg'] = 'Invalid user. Please Login and try again.';
            return $status;
        } else if (!isset($cart)) {
            $status['details']['error_msg'] = 'We Could not find the cart. We\'re working on it!';
            return $status;
        } else if (!isset($promo_code) || strlen($promo_code) < 2) {
            $status['details']['error_msg'] = 'Seems like you\'ve used an invalid code. Please check the code.';
            return $status;
        }


        /*
        * promo code is valid if
        * 1.0 Promo code expiry date should be in future 
        * 1. LS_ID of products, Discount will be given on products that have LS_ID match with PROMO CODE
        * 2. Users must qualify for the PROMO CODE, i.e they must have there domain mentioned in the allowed_users col
            '*' is for `valid for all users` and
        * 3. Users must qualify the total use limit of a promo code
        *
        */

        $promo_details = PromoDiscount::where('code', $promo_code)->where('is_active', '1')->get()->toArray();
        if (sizeof($promo_details) != 1) {
            $status['details']['error_msg'] = 'Seems like you\'ve used an invalid code. Please check the code.';
            return $status;
        }

        $promo_details = $promo_details[0];

        /********************************************************************
         *********************************************************************
         ******** CHANGE THIS CODE TO FACTORY PATTERN WHEN YOU GET TIME!!!****
         *********************************************************************
         *********************************************************************/
        if (self::is_promo_not_expired($promo_details)) {
            // if user can apply this promo code
            if (self::is_promo_count_valid($user, $promo_details)) {

                // if this user is is in special group that allows them to apply 
                // the promo code
                if (self::is_user_allowed($user, $promo_details)) {
                    $status['is_valid'] = true;
                    $status['details']['discount_details'] = $promo_details;
                    return $status;
                } else {
                    $status['details']['error_msg'] = 'Please sign in with a valid email to use this promo code.';
                    return $status;
                }
            } else {
               // $status['details']['error_msg'] = 'Seems like you have already exhausted maximum limit for this discount code.';
				$status['details']['error_msg'] = 'This promo code has expired or been fully redeemed. Please contact us if you need further assistance.'; 
                return $status;
            }
        } else {
            $status['details']['error_msg'] = "Seems like this coupon is already expired.";
            return $status;
        }


        return $status;
    }


    /**
     * returns simple true and false based on the domain (email) of users
     * this is for companies
     */
    private static function is_user_allowed($user, $promo_details)
    {

        $user_mail = $user->email;
        $domain = explode("@", $user_mail);
        $allowed_domains = explode(",", $promo_details['special_users']);

        if (in_array("*", $allowed_domains))
            return true;

        if (sizeof($domain) != 2)
            return false;

        // abs@gmail.com domain = gmail
        $domain = explode(".", $domain[1])[0];

        return in_array($domain, $allowed_domains);
    }

    private static function is_promo_count_valid($user, $promo_details)
    {

        $allowed_count = $promo_details['allowed_count'];
        $availed_count = DB::table('lz_promo_users')
            ->where('user_id', $user->id)
            ->where('promo_code', $promo_details['code'])
            ->count();


        return $availed_count < $allowed_count;
    }

    /**
     * Return empty array if no products match the promo code LS_ID
     * else return matched SKU array
     *
     * @param [array] $cart
     * @param [Object] $promo_details
     * @return boolean
     */
    private static function LSIDs_allowed($cart, $promo_details)
    {
        // get all product SKUs to find their LS_IDs
        $in_cart_skus = [];
        foreach ($cart['products'] as $product) {
            $in_cart_skus[] = $product->product_sku;
        }
 
		//return $in_cart_skus;
        // [SKU] => "lsid1,lsid2,lsid3..."
        $sku_lsid_map = self::get_product_LSID($in_cart_skus);

        $promo_lsids = explode(",", $promo_details['applicable_categories']);

        // if promo is valid for all categories 
        // return all in-cart SKUs
        if (in_array("*", $promo_lsids)) {
            $in_cart_skus = self::check_for_mfg_country($in_cart_skus, $cart['products'], $promo_details);
            $in_cart_skus = self::check_for_brand($in_cart_skus, $cart['products'], $promo_details);

            return $in_cart_skus;
        }

        $sku_available_for_promo = [];
        foreach ($sku_lsid_map as $sku => $lsid_str) {
            $lsid_arr = explode(",", $lsid_str);
            foreach ($lsid_arr as $lsid) {
                if (in_array($lsid, $promo_lsids))
                    $sku_available_for_promo[] = $sku;
            }
        }


        $sku_available_for_promo = self::check_for_mfg_country($sku_available_for_promo, $cart['products'], $promo_details);
        $sku_available_for_promo = self::check_for_brand($sku_available_for_promo, $cart['products'], $promo_details);
        return $sku_available_for_promo;
    }


    /**
     * Return the SKUs allowed for promo that satisfy the brand name constraint 
     * given in the promo details
     *
     */
    public static function check_for_brand($valid_skus, $cart_products, $promo_details)
    {

        $allowed_SKUs = [];
        $valid_brands = $promo_details['applicable_brands'];

        if ($valid_brands == "*")
            return $valid_skus;

        if (!isset($valid_brands) || strlen($valid_brands) == 0)
            return [];

        $valid_brands = explode(",", $valid_brands);
        foreach ($cart_products as $product) {
            if (in_array($product->product_sku, $valid_skus)) {

                if (in_array($product->brand_id, $valid_brands)) {
                    $allowed_SKUs[] = $product->product_sku;
                }
            }
        }

        return $allowed_SKUs;
    }

    /**
     * filter SKUs that pass the mfg_country check
     * mfg country for the product must match the mfg country there in the inventory table
     *
     */
    public static function check_for_mfg_country($valid_skus, $cart_products, $promo_details)
    {
        $allowed_SKUs = [];

        // if there is no mfg_contry in the inventory table then
        // no need to add this check

        //echo json_encode($promo_details);
        if (
            !isset($promo_details['mfg_country'])
            || strlen($promo_details['mfg_country']) == 0
        )
            return $valid_skus;

        if ($promo_details['mfg_country'] == "*")
            return $valid_skus;


        foreach ($cart_products as $product) {

            if (isset($product->mfg_country) && strlen($product->mfg_country) > 0) {

                $product_mfg_contries = explode(",", strtolower($product->mfg_country));
                $inventory_product_mfg_contries = explode(",", strtolower($promo_details['mfg_country']));

                foreach ($product_mfg_contries as $country) {

                    if (in_array($country, $inventory_product_mfg_contries)) {
                        $allowed_SKUs[] = $product->product_sku;
                        break;
                    }
                }
            } else {
                // only those SKU will match which match the mfg_country
                //$allowed_SKUs[] = $product->product_sku;    
            }
        }

        return $allowed_SKUs;
    }

    /**
     * Check if promo is expired or not
     *
     * @param [object] $promo_details
     * @return boolean
     */
    private static function is_promo_not_expired($promo_details)
    {
        $promo_expiry_date = $promo_details['expiry'];
        $today = time();
        $expiry = strtotime($promo_expiry_date);

        // expiry should be a future date, hence it should be greater that now()
        return ($expiry - $today) > 0;
    }


	public static function save_promocode($data){
	
	
		$code = empty($data['code'])?'':$data['code'];
		$name = empty($data['name'])?'':$data['name'];
		$description = empty($data['description'])?'':$data['description'];
		$type = empty($data['type'])?'':$data['type'];
		$value = empty($data['value'])?'':$data['value'];
		$applicable_brands = empty($data['applicable_brands'])?'':$data['applicable_brands'];
		$applicable_categories = empty($data['applicable_categories'])?'':$data['applicable_categories'];
		$mfg_country = empty($data['mfg_country'])?'':$data['mfg_country'];
		$allowed_count = empty($data['allowed_count'])?'':$data['allowed_count'];
		$special_users = empty($data['special_users'])?'':$data['special_users'];
		$expiry = empty($data['expiry'])?'':$data['expiry'];
		$is_active = empty($data['is_active'])?'':$data['is_active'];
		$apply_on = empty($data['apply_on'])?'':$data['apply_on'];
		$is_SKU_specific = empty($data['is_SKU_specific'])?'':$data['is_SKU_specific'];
		$product_sku = empty($data['product_sku'])?'':$data['product_sku'];
		$parent_sku = empty($data['parent_sku'])?'':$data['parent_sku'];
		
		$error = [];
		$arr = []; 
		
			 
		$is_inserted = DB::table('lz_promo')
                    ->insertGetId([
								'code' =>  $code,
								'name' => $name,
								'description' => $description,
								'type' => $type,
								'value' => $value,
								'applicable_brands' => $applicable_brands,
								'applicable_categories' => $applicable_categories, 
								'mfg_country' => $mfg_country,
								'allowed_count' => $allowed_count, 
								'special_users' => $special_users,
								'expiry' => $expiry,
								'is_active' => $is_active, 
								'apply_on' => $apply_on,
								'is_SKU_specific' => $is_SKU_specific
							]);
		if($is_inserted>0){
			if($is_SKU_specific==1){
				if($product_sku!=''){
							$is_insert= DB::table('lz_inventory')
								->whereIn('product_sku', $product_sku)
								->update(['promo_id' => '1']);
				}
				if($parent_sku!=''){
					$is_insert= DB::table('lz_inventory')
								->whereIn('parent_sku', $parent_sku)
								->update(['promo_id' => '1']);
				}
				
			}
			$a['status']=true;
		}
		else{
			$a['status']=false;
		}
		
		$a['errors'] = $error;
	
        return $a;
    }
	
	private static function clearance_filter($allowed_SKUs, $clearancefilter){
	//	return $allowed_SKUs;
		$sql = DB::table('master_data') 
				->select('product_sku') 
				->where('is_clearance', $clearancefilter) 
				->whereIn('product_sku', $allowed_SKUs )
				//->toSql();
				->get();
		$arr = [];
		if($sql=='[]'){ 
						
		}else{
				 
				foreach($sql as $data){
					array_push($arr,$data->product_sku);
				} 
			   
		}
		return $arr; 
	}
	
	public static function decreasePromoCount($cart, $promo_code){

	   $user = Auth::user(); 
        // first check if the promo code is valid or not.
        // fast fail system.
        $promo_status = self::check_promo_code($user, $cart, $promo_code);  
        if (!$promo_status['is_valid']) {
            $cart['promo_details'] = $promo_status['details'];
            return $cart;
        }

		
		 $promo_details = $promo_status['details']['discount_details']; 
		

		$allow_count = $promo_details['allowed_count']-1;
		$sql = DB::table('lz_promo')
                    ->where('id', $promo_details['id'])
                    ->update(['allowed_count' => $allow_count]);


        return 'Success';
	}

    private function unique_multidim_array($array, $key) {  
        $i = 0;
        $j = 0;
        $key_array1 = []; 
        $key_array2 = []; 
        $key_array = []; 
        foreach($array['products'] as &$val) {
            if (!in_array($val->$key, $key_array)) {
               /* if((substr($val->$key,0,2))==config('shipping.rate_shipping')){
                    $key_array1[$i] = $val->$key; 
                    $i++;
                }
                    
                else if((substr($val->$key,0,2))==config('shipping.fixed_shipping')){
                    $key_array2[$j] = $val->$key;
                    $j++;
                }*/
                 
                $key_array[$i] = $val->$key; 
                    $i++;
                
            }
            
        }
       // $key_array['sv'] = $key_array1;
      //  $key_array['wg'] = $key_array2;
       // $key_array['total'] = count($key_array1)+count($key_array2);
       //$key_array['total'] = count($key_array);
        return $key_array;
    }

    private function unique_multidim_array_shipcode($array, $key) {  
        $i = 0;
        $j = 0;
        $key_array1 = []; 
        $key_array2 = []; 
        $key_array = []; 
        foreach($array['products'] as &$val) {
            if (!in_array($val->$key, $key_array)) {
                 if((substr($val->$key,0,2))==config('shipping.rate_shipping')){
                    $key_array1[$i] = $val->$key; 
                    $i++;
                }
                    
                else if((substr($val->$key,0,2))==config('shipping.fixed_shipping')){
                    $key_array2[$j] = $val->$key;
                    $j++;
                } 
                 
                $key_array[$i] = $val->$key; 
                    $i++;
                
            }
            
        }
        $key_array['sv'] = $key_array1;
        $key_array['wg'] = $key_array2;
        $key_array['total'] = count($key_array1)+count($key_array2); 
        return $key_array;
    }

}
