<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use PhpParser\Node\Expr\Variable;
use Auth;

// majorly writen for westelm products

class SellerProduct extends Model
{
    public static $base_siteurl = 'https://www.lazysuzy.com';
    public static $col_mapper = [
        "color" => "attribute_1",
        "fabric" => "attribute_2",
        "delivery" => "attribute_3",
        "shape" => "attribute_2",
        "furniture_piece" => "attribute_4",
        "leg_style" => "attribute_6"
    ];


  public static function save_sellerProduct($data) {
		
		$user_id = 0;
		$is_authenticated = Auth::check();
		$user = Auth::user(); 
		$user_id = $user->id;
		$brandname = '';
		$brandid = '';
		$error = [];
		$a['status'] = true;
		
		$query_brand  = DB::table('seller_brands')->select("*")->whereRaw("user_id=".$user_id)->get();
        if(!empty($query_brand) && sizeof($query_brand)>0) {		
			$brandname = $query_brand[0]->value;
			$brandid = $query_brand[0]->id;
		}
		
		if(isset($data['product_sku']) && $data['product_sku']!='null'){ 
			$product_sku = $data['product_sku'];
		}
		else{
				$randno= rand(1,999999);
				$product_sku ='21'.$randno;
				
		}
		
		 $querysku = DB::table('seller_products')->select(DB::raw('COUNT(id) as cnt_sku'))->where('product_sku', '=', $product_sku)->get();	
		 
		 if( $querysku[0]->cnt_sku > 0){
		    $error[] = response()->json(['error' => 'Product Sku already exists','key'=>'product_sku'], 422);
			$a['status']=false;
	    //    $a['errors'] = $error;
		//	return $a;
		 
		 }
		
		if(isset($data['product_name']) && $data['product_name']!='null'){ 
			$product_name = $data['product_name'];
		}
		else{
				$product_name ='' ;
				$error[] = response()->json(['error' => 'Add a product name here','key'=>'product_name'], 422);
				$a['status']=false;
		}
		if(isset($data['description']) && $data['description']!='null'){ 
			$product_description = $data['description'];
		}
		else{
				$product_description ='' ;
				$error[] = response()->json(['error' => 'Let customers know why they\'ll love your product!','key'=>'description'], 422);
				$a['status']=false;
		}
		if(isset($data['features']) && $data['features']!='null'){ 
			$product_feature = $data['features'];
		}
		else{
				$product_feature ='' ;
				$error[] = response()->json(['error' => 'Share key highlights on your product.','key'=>'features'], 422);
				$a['status']=false;
		}
		if(isset($data['assembly']) && $data['assembly']!='null'){ 
			$product_assembly = $data['assembly'];
		}
		else{
				$product_assembly ='' ;
		}
		if(isset($data['care']) && $data['care']!='null'){ 
			$product_care = $data['care'];
		}
		else{
				$product_care ='' ;
		}
		
		if(isset($data['style']) && $data['style']!='null'){ 
			$style = $data['style'];
		}
		else{
				$style ='' ;
		}
		if(isset($data['shape']) && $data['shape']!='null'){ 
			$shape = $data['shape'];
		}
		else{
				$shape ='' ;
		}
		if(isset($data['seats']) && $data['seats']!='null'){ 
			$seating = $data['seats'];
		}
		else{
				$seating ='' ;
		}
		
		if(isset($data['firmness']) && $data['firmness']!='null'){ 
			$firmness = $data['firmness'];
		}
		else{
				$firmness ='' ;
		}
		if(isset($data['country']) && $data['country']!='null'){ 
			$mfg_country = $data['country'];
		}
		else{
				$mfg_country ='' ;
		}
		
		if(isset($data['is_handmade']) && $data['is_handmade']!='null'){ 
			$is_handmade = $data['is_handmade'];
		}
		else{
				$is_handmade ='' ;
		}
		if(isset($data['sustainably_sourced']) && $data['sustainably_sourced']!='null'){ 
			$is_sustainable = $data['sustainably_sourced'];
		}
		else{
				$is_sustainable ='' ;
		}
		if(isset($data['shipping_type']) && $data['shipping_type']!='null'){ 
			$shipping_code = $data['shipping_type'];
		}
		else{
				$shipping_code ='' ;
				$error[] = response()->json(['error' => 'Please enter your selection for shipping type.','key'=>'shipping_type'], 422);
				$a['status']=false;
		}
		 
		
		$lsid = '';
		
		if (array_key_exists('categories', $data) && isset($data['categories'])){	
		    
			$lsarr = [];
			for($i=0;$i<(count($data['categories'])-1);$i++){
					if($data['categories'][$i]['department']!='' && $data['categories'][$i]['department']!='null'){
					
						 $query = DB::table("mapping_core")
						->select(['LS_ID'])
						->where('dept_name_url', $data['categories'][$i]['department']);
						
						if($data['categories'][$i]['category']!='' && $data['categories'][$i]['category']!='null'){
							$query = $query->where('cat_name_url',$data['categories'][$i]['category']);
						}
						if($data['categories'][$i]['sub_category']!='' && $data['categories'][$i]['sub_category']!='null'){
							$query = $query->where('cat_sub_url', $data['categories'][$i]['sub_category']);
						}
						$query = $query->get();
						
						foreach($query as $row){
							array_push($lsarr,$row->LS_ID);
						}
						 $lsid = implode(",",$lsarr); 
					
					}
			}
		}
		else{
		
				$error[] = response()->json(['error' => 'Select at least one category where customers can find your product.','key'=>'categories'], 422);
				$a['status'] = false;
		}
		
		if (array_key_exists('dimensions', $data) && isset($data['dimensions'])){	
			$dimensions = json_encode($data['dimensions']);
		}
		else{
				$dimensions = '';
		}	
		
		if (array_key_exists('colors', $data) && isset($data['colors'])){	
			$color = json_encode($data['colors']);
		}
		else{
				$color = '';
		}	
		
		if (array_key_exists('materials', $data) && isset($data['materials'])){
			$material = json_encode($data['materials']);
		}
		else{
				$material = '';
		}	
		
		
		
		
		// return 'dddd='.$data['variation_structure'];
		$variations = '[]';
		$product_images = '';
		$product_main_images = '';
		$quantity ='' ;
		$variations_count = 0;
		$datetime = date("Y-m-d H:i:s");
		$price ='' ; 
		
		$has_variations = $data['has_variations'];
		if($has_variations==true){
				if (isset($data['variation_structure']) && $data['variation_structure']!='null') {
		 

					$variations = json_encode($data['variation_structure']);
					$variations_count = count($data['variations']);
				}

        }
		else{
				if(isset($data['price']) && $data['price']!='null' && $data['price']!=''){ 
					$price = $data['price'];
				}
				else{
						$price ='' ; 
				}
				
				if(isset($data['quantity']) && $data['quantity']!='null' && $data['quantity']!=''){ 
					$quantity = $data['quantity'];
				}
				else{
						$quantity ='' ;
						
				}
				if($price == '' && $quantity == ''){
					$error[] = response()->json(['error' => 'Please enter your product price and quantity information.','key'=>'price_quantity'], 422);
						$a['status'] = false;
				}
		
		
		}


		if (array_key_exists('product_images', $data) && isset($data['product_images'])) {
			
				//$upload_folder = public_path('images/seller/');//return 'update='. $upload_folder;
				$upload_folder = '/var/www/html/lazysuzy-code/seller/';
				for($i=0;$i<count($data['product_images']);$i++){
						
						$image_parts = explode(";base64,", $data['product_images'][$i]);
						$image_type_aux = explode("image/", $image_parts[0]);
						$image_type = $image_type_aux[1];
						$image_base64 = base64_decode($image_parts[1]);
						
						$image_name = $upload_folder.time() . '-' . Utility::generateID() . '.'. $image_type ;
						$uplaod =  file_put_contents($image_name, $image_base64);  
						$arr[$i]['image'] = 'images/seller/'.$image_name;
				return $uplaod;
					} 
					
					if($uplaod) {
						$product_main_images = $arr[0]['image'];
						$product_images = json_encode($arr);
					}
					else 
						$error[] = response()->json(['error' => 'image could not be uploaded. Please try again.'], 422);
					
				
		}
		else{
				$error[] = response()->json(['error' => 'Add atleast one image to showcase your product.','key'=>'product_images'], 422);
				$a['status'] = false;
		}
		
		
		
		$desc_sub = [];
		$datajson = '';
		 
		
	    if( $a['status']){
		 $is_inserted = DB::table('seller_products')
                    ->insertGetId([
								'product_images' =>  $product_images,
								'main_product_images' =>  $product_main_images,
								'product_sku' =>  $product_sku,
								'product_name' =>  $product_name,
								'product_description' =>  $product_description,
								'product_feature' =>  $product_feature,
								'product_assembly' =>  $product_assembly,
								'product_care' =>  $product_care,
								'color' =>  $color,
								'material' =>  $material,
								'style' =>  $style,
								'shape' =>  $shape,
								'seating' =>  $seating,
								'firmness' =>  $firmness,
								'mfg_country' =>  $mfg_country,
								'is_handmade' =>  $is_handmade,
								'is_sustainable' =>  $is_sustainable,
								'variations' =>  $variations,
								'serial' =>  $brandid,
								'brand' =>  $brandname,
								'LS_ID' =>  $lsid,
								'submitted_id' => $user_id,
								'shipping_code' => $shipping_code,
								'quantity' => $quantity,
								'variations_count' => $variations_count,
								'created_date' => $datetime,
								'min_price' => $price,
								'max_price' => $price,
								'min_was_price' => $price,
								'max_was_price' => $price,
								'updated_date' => $datetime,
								'product_dimension' => $dimensions,
							]);
			if($is_inserted>0){
				
				 
				
					if ($has_variations && array_key_exists('variations', $data) && isset($data['variations'])) {
						
					$arr2 = [];
					$min_price = 1000000;
					$min_was_price = 1000000;
					for($i=0;$i<count($data['variations']);$i++){
						$arr2 = $data['variations'][$i];
						$variation_images = '';
						/*if (isset($arr2['image']) && $arr2['image']!='null') {
								$arr1 = [];	
								$upload_folder = public_path('public/images/uimg');
									for($j=0;$i<count($arr2['image']);$j++){
										//$img =  strip_tags($arr2['image'][$j]); 
										$img = isset($arr2['image'][$j]) ? $arr2['image'][$j] : null;
										$image_parts = explode(";base64,",strip_tags($img));
										$imgprt0 = isset($image_parts[0]) ? $image_parts[0] : null;
										$imgprt1 = isset($image_parts[1]) ? $image_parts[1] : null;
										$image_type_aux = explode("image/", strip_tags($imgprt0));
										$image_type = isset($image_type_aux[1]) ? $image_type_aux[1] : null;
										$image_base64 = base64_decode($imgprt1);
										
										$image_name = time() . '.'. $image_type ;
										$uplaod =  file_put_contents($image_name, $image_base64);
										$arr1[$j] = 'images/uimg/'.$image_name;
								
									} 
									return $arr1;
									 if($uplaod) {
										$variation_images = json_encode($arr1);
									}
									else 
										$error[] = response()->json(['error' => 'image could not be uploaded. Please try again.'], 422);
									 
								
						}*/
						$product_id = $is_inserted;
						$status = $arr2['available']==1 ? 'active' : 'inactive';
						$name = empty($arr2['product_name']) ? '' : $arr2['product_name'];
						$sku = empty($arr2['product_sku']) ? $product_sku.'-00'.($i+1) : $arr2['product_sku'];
						$qty = empty($arr2['quantity']) ? '' : $arr2['quantity']; 
						$was_price = empty($arr2['list_price']) ? 0 : $arr2['list_price']; 
						$price = empty($arr2['sale_price']) ? $was_price : $arr2['sale_price'];
						
						
						if($min_price > $price){
							$min_price = $price;
						}
						
						if($min_was_price > $was_price){
							$min_was_price = $was_price;
						}
						
						$opt = isset($arr2['options']) ? $arr2['options'] : null;
						$k=0; 
						$optarr = [];
						$pname = '';
						foreach($opt as $key => $val) {
							
							$optarr[$k] = $key.':'.$val;
							$pname = $pname.' '.$val;
							
							$k++;
						}
						if($name==''){
							$name = $pname;
						}
						
						$is_variation_inserted = DB::table('seller_products_variations')
						->insert([
									'product_id' =>  $product_sku,
									'sku' =>  $sku,
									'name' =>  $name,
									'price' =>  $price,
									'was_price' =>  $was_price,
									'qty' =>  $qty,
									'attribute_1' =>  isset($optarr[0]) ? $optarr[0] : '',
									'attribute_2' =>  isset($optarr[1]) ? $optarr[1] : '',
									'attribute_3' =>  isset($optarr[2]) ? $optarr[2] : '',
									'attribute_4' =>  isset($optarr[3]) ? $optarr[3] : '',
									'attribute_5' =>  isset($optarr[4]) ? $optarr[4] : '',
									'attribute_6' =>  isset($optarr[5]) ? $optarr[5] : '',
									'status' =>  $status,
									'created_date' => $datetime,
									'updated_date' => $datetime,
									
								]);
						
						$arr2= [];
					
					}
					
					 $updateDB =  DB::table('seller_products')
									->where('product_sku', $product_sku)
									->update([
												'min_price' => $min_price,
												'max_price' => $min_price,
												'min_was_price' => $min_was_price,
												'max_was_price' => $min_was_price,
											]);
					
					
					
				}
				
			 
				
				
				$a['status']=true;
			}
			else{
				$a['status']=false;
			}
		}
		$a['errors'] = $error;
	
        return $a;

     
        
    }
	
	
	
	public static function get_sellerProductInfo(){
		
		// Get Variation Information
		
        $all_label = [];
        $query       = DB::table('variations')->select(['var_ID','var_label','var_value','var_unit'])->get(); 
		
		$all_variation = [];
		foreach ($query as $row){
			$all_variation['var_ID'] = $row->var_ID;
			$all_variation['var_label'] = $row->var_label;
			$all_variation['var_type'] = 2;
			$all_variation['options'] = [];
			
			if($row->var_label=='Color') {
				$all_variation['var_type'] = 1;
				$all_variation['options'] = (explode(",",$row->var_value));
			}
			if($row->var_label=='Width') {
				$all_variation['var_type'] = 3;
				$all_variation['options'] = (explode(",",$row->var_unit));
			}
            array_push($all_label, $all_variation);
	    } 
		
		
		//Get Shipping Information
		
		$query1       = DB::table('lz_ship_code')->whereNull('brand_id')->get();
		 
		$all_shipping = [];
		foreach ($query1 as $row){
			
			
            array_push($all_shipping, $row);
	    }
			
		$a['all_label']= $all_label;
		$a['all_shipping']= $all_shipping;
		
		return $a; 
	}


	public static function get_sellerProductList(){
		$user_id = 0;
		$is_authenticated = Auth::check();
		$user = Auth::user(); 
		$user_id = $user->id;
		//$user_id = 1093;
		$query       = DB::table('seller_products')
						->where('submitted_id', $user_id)
						->join("seller_brands", "seller_products.brand", "=", "seller_brands.value") 
						->get();
		 
		$all_products = [];
		foreach ($query as $row){
			$row->variations = json_decode($row->variations);
            array_push($all_products, $row);
	    }
		return $all_products;
	}
}
