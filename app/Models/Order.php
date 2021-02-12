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
			           ->join('lz_order_dump', 'lz_order_dump.order_id', '=', 'lz_order_delivery.order_id')	 
						->select('lz_order_dump.order_id','lz_order_delivery.shipping_f_name','lz_order_delivery.shipping_l_name','lz_order_delivery.shipping_address_line1','lz_order_delivery.shipping_address_line2','lz_order_delivery.shipping_state','lz_order_delivery.shipping_zipcode','lz_order_delivery.order_id','lz_order_delivery.shipping_city','lz_order_delivery.created_at','lz_order_dump.order_json');
			

			$is_authenticated = Auth::check();
			$user = Auth::user(); 
            if ($user->user_type>0) {	
					 
					$data = $data
					->where('lz_order_delivery.user_id', $user->id);
			}
			else{
					if ($orderid != '' && $zipcode != ''){
						$data = $data
							->where('lz_order_delivery.order_id', $orderid)
							->where('lz_order_delivery.shipping_zipcode', $zipcode);
					}
				     else{
						 
							$response['status']=false;
							$response['msg']='Order Number & Zipcode both are required.';
							return $response;
					 }
					
				
			}

		
			 
			$data = $data->orderBy("lz_order_delivery.created_at", "DESC")->get(); 
			if($data!='[]'){
				$response['status']=true;
				
				foreach($data as $datasingle){  
				   $datasingle->created_at = date("F j, Y", strtotime($datasingle->created_at));
				   
				  
				   foreach((json_decode($datasingle->order_json)->products) as $prod){
					   
				 
					$product_rows_child = DB::table('lz_orders') 
					->where('product_sku', $prod->product_sku)   
					->where('order_id', $datasingle->order_id) 					
					->select(array('lz_orders.quantity','lz_orders.status','lz_orders.note','lz_orders.date','lz_orders.tracking','lz_orders.tracking_url','lz_orders.delivery_date'))
					->get();
					   
					 $prod->quantity = $product_rows_child[0]->quantity;  
					 $prod->status = $product_rows_child[0]->status;  
					 $prod->note = $product_rows_child[0]->note;  
					 $prod->date = $product_rows_child[0]->date;  
					 $prod->tracking = $product_rows_child[0]->tracking;  
					 $prod->tracking_url = $product_rows_child[0]->tracking_url;  
					 $prod->delivery_date = $product_rows_child[0]->delivery_date;   
					 
					
					  array_push($arr,$prod);
					   
					   
				   }
				    $datasingle->products = $arr; 
					array_push($arrheader,$datasingle); 
					$arr = [];
				    
				    
					 
				}	
				
			
			}
			else{
					$response['status']=false;
					$response['msg']='Order not found. Please check your order details or contact us for further assistance';
			}
			$response['data']=$arrheader;	
		 
		
		return $response;
	}


   
};
