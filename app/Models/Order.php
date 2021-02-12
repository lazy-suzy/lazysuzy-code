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

class Order extends Model
{ 	
	public static function get_order_status()
	{
			$orderid   = Input::get("orderid");
			$zipcode   = Input::get("zipcode");
			$arr = []; 
			$arrheader = []; 
			$data   = DB::table('lz_order_delivery')
						->select('shipping_f_name','shipping_l_name','shipping_address_line1','shipping_address_line2','shipping_state','shipping_zipcode','order_id','shipping_city','created_at');
			

			$is_authenticated = Auth::check();
			$user = Auth::user(); 
            if ($user->user_type>0) {	
					 
					$data = $data
					->where('user_id', $user->id);
			}
			else{
					if ($orderid != '' && $zipcode != ''){
						$data = $data
							->where('order_id', $orderid)
							->where('shipping_zipcode', $zipcode);
					}
				     else{
						 
							$response['status']=false;
							$response['msg']='Order Number & Zipcode both are required.';
							return $response;
					 }
					
				
			}

		
			 
			$data = $data->get(); 
			if($data!='[]'){
				$response['status']=true;
				
				foreach($data as $datasingle){ 
				   $datasingle->created_at = date("F j, Y", strtotime($datasingle->created_at));
				   
					$product_rows_child = DB::table('lz_orders') 
					->where('order_id', $datasingle->order_id)    	
					->join('master_data', 'master_data.product_sku', '=', 'lz_orders.product_sku')		
					->join('lz_inventory', 'lz_inventory.product_sku', '=', 'lz_orders.product_sku')		
					->join('lz_ship_code', 'lz_ship_code.code', '=', 'lz_inventory.ship_code')		
					->join('master_brands', 'master_brands.value', '=', 'master_data.brand')	
					->select(array('lz_orders.quantity','lz_orders.price','lz_orders.status','lz_orders.note','lz_orders.date','lz_orders.tracking','lz_orders.tracking_url','lz_orders.delivery_date','lz_inventory.ship_code','lz_ship_code.label','master_data.product_name','master_data.main_product_images as image','master_brands.name as brand_name','master_data.product_sku','lz_ship_code.label'))
					->get(); 
				
					
					foreach($product_rows_child as $pr){
						$pr->date = date("F j, Y", strtotime($pr->date));
					 	$pr->image =  env('APP_URL').$pr->image; 
						array_push($arr,$pr);
					
					}
					$datasingle->products = $arr;
					array_push($arrheader,$datasingle); 
					$arr = [];
					 
				}	
				
			
			}
			else{
					$response['status']=false;
					$response['msg']='No data found.';
			}
			$response['data']=$arrheader;	
		 
		
		return $response;
	}


   

};
