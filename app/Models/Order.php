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

    public static function get_order_list(){

       $arr = [
				'lz_orders.order_id',
				'lz_order_delivery.created_at',
				'lz_order_delivery.shipping_f_name',
				'lz_order_delivery.shipping_l_name',
				'lz_order_delivery.shipping_address_line1',
				'lz_order_delivery.shipping_address_line2',
				'lz_order_delivery.shipping_city',
				'lz_order_delivery.shipping_state',
				'lz_order_delivery.shipping_zipcode',	
				'lz_orders.product_sku',	
				'lz_orders.price',	
				'lz_orders.note',
				'lz_orders.delivery_date',	
				'lz_orders.tracking_url',	
				'lz_orders.tracking',	
				'lz_orders.status as status_code',	
				'lz_orders.quantity',	
				'lz_order_code.label as status_label',	
				'lz_order_code.bg_hex',	
				'lz_order_code.font_hex',	
	   		  ];


		$data   = DB::table('lz_orders')
					->join('lz_order_delivery', 'lz_orders.order_id', '=', 'lz_order_delivery.order_id')	
					->join('lz_order_code', 'lz_orders.status', '=', 'lz_order_code.code')	 
					->select($arr)
					->orderby('lz_orders.id', 'desc')
					->get();

        foreach($data as $row){
			$row->parent_sku = NULL;
			$data_parent   = DB::table('lz_inventory')	 
					->select(['parent_sku'])
					->WHERE('product_sku',$row->product_sku)
					->get();
			if(isset($data_parent[0]->parent_sku)){
				$row->parent_sku = $data_parent[0]->parent_sku;
			}		
			$row->created_at = date("F j, Y", strtotime($row->created_at));
			//$row->status_code = $row->status;
			//$row->status_label = $row->label;
		}
		return $data;
			
	}

	public static function update_order($alldata) {
		if (isset($alldata)) {
			foreach ($alldata as $data) {
				//$data = $alldata[$i];
				
				if(!isset($data['note'])){
					$data['note']=NULL;
				}
				if(!isset($data['delivery_date'])){
					$data['delivery_date']=NULL;
				}
				if(!isset($data['tracking_url'])){
					$data['tracking_url']=NULL;
				}
				if(!isset($data['tracking'])){
					$data['tracking']=NULL;
				}
				if(!isset($data['status'])){
					$data['status']=NULL;
				}
			 
				  $error = [];
				 
				  $is_inserted =  DB::table('lz_orders')
					->WHERERAW("order_id='".$data['order_id']."' AND product_sku='".$data['product_sku']."'") 
					->update([
					  'note' =>  $data['note'],
					  'delivery_date' =>  $data['delivery_date'],
					  'tracking_url' =>  $data['tracking_url'],
					  'tracking' =>  $data['tracking'],
					  'status' =>  $data['status']
					]);
			
			}
		
		
		
		}
		
		
			  



		/*if($is_inserted==1){
			$a['status']=true;
		}
		else{
			$a['status']=false;
		}*/
		
		$a['errors'] = false;
	
        return $a;
    }


   
};
