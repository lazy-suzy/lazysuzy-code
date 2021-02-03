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
    protected $table = "master_data";
    public static $base_siteurl = 'https://www.lazysuzy.com';
	
	
	public static function get_order_history($user_id)
    {
		$head_array = [];
		if($user_id>0)
		{
			
			$product_rows = DB::table('lz_order_delivery') 
			->where('user_id', $user_id)   						
			->select(*)
			->get();
				
			foreach($product_rows as $pr) {  
				array_push($head_array,$pr); 
					
				$product_rows_child = DB::table('lz_orders') 
				->where('product_sku', $product_rows->$product_rows)   						
				->select(*)
				->get();

				
				$head_array->orders = 	$product_rows_child ;
			}
			
			$a['status']=true;
			$a['response'] = $head_array;
			
			
		}
		else{
				$a['status']=false;
				$a['msg']='Not authenticaticated User.';
		}	

		return $a;	
	}

   


};
