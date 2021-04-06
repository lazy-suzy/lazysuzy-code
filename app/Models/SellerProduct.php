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
		
		
		$is_authenticated = Auth::check();
			$user = Auth::user(); 
			
		$product_sku 			= empty($data['product_sku']) ? '' : $data['product_sku'];
		$product_name 			= empty($data['product_name']) ? '' : $data['product_name'];
		$product_description 	= empty($data['description']) ? '' : $data['description'];
		$product_feature 		= empty($data['fearures']) ? '' : $data['fearures'];
		
		$product_assembly	 	= empty($data['assembly']) ? '' : $data['assembly'];
		$product_care 			= empty($data['care']) ? '' : $data['care'];
		
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
		 
		$color = empty($data['colors']) ? '' : $data['colors'];
		$material = empty($data['materials']) ? '' : $data['materials'];
		$style = empty($data['style']) ? '' : $data['style'];
		$shape = empty($data['shape']) ? '' : $data['shape'];
		$seating = empty($data['seats']) ? '' : $data['seats'];
		
		$firmness = empty($data['firmness']) ? '' : $data['firmness'];
		$mfg_country = empty($data['country']) ? '' : $data['country'];
		$is_handmade = empty($data['is_handmade']) ? '' : $data['is_handmade'];
		$is_sustainable = empty($data['sustainably_sourced']) ? '' : $data['sustainably_sourced'];
		//$brand = empty($data['brand']) ? '' : $data['brand'];
		
		$variations = '';
		$product_images = '';
		
		if (array_key_exists('variation_structure', $data) && isset($data['variation_structure'])) {

            $variations = json_encode($data['variation_structure']);
		}

		if (array_key_exists('product_images', $data) && isset($data['product_images'])) {
			
				$upload_folder = public_path('public/images/uimg');
					for($i=0;$i<count($data['product_images']);$i++){
						
						$image_parts = explode(";base64,", $data['product_images'][$i]);
						$image_type_aux = explode("image/", $image_parts[0]);
						$image_type = $image_type_aux[1];
						$image_base64 = base64_decode($image_parts[1]);
						
						$image_name = time() . '-' . Utility::generateID() . '.'. $image_type ;
						$uplaod =  file_put_contents($image_name, $image_base64);
						$arr[$i]['image'] = 'images/uimg/'.$image_name;
				
					} 
					
					if($uplaod) {
						$product_images = json_encode($arr);
					}
					else 
						$error[] = response()->json(['error' => 'image could not be uploaded. Please try again.'], 422);
					
				
		}
		
		
		$error = [];
		$desc_sub = [];
		$datajson = '';
		/*if(array_key_exists('width', $data) && isset($data['width'])) {
			
			$desc_sub['width'] = json_encode($data['width']);
		}
		if(array_key_exists('fabric', $data) && isset($data['fabric'])) {
			
			$desc_sub['fabric'] = json_encode($data['fabric']);
		}
		if(array_key_exists('finish', $data) && isset($data['finish'])) {
			
			$desc_sub['finish'] = json_encode($data['finish']);
		}
		if(array_key_exists('material', $data) && isset($data['material'])) {
			
			$desc_sub['material'] = json_encode($data['material']);
		}
		if(array_key_exists('color', $data) && isset($data['color'])) {
			
			$desc_sub['color'] = json_encode($data['color']);
		}
		
		$datajson =  json_encode($desc_sub);*/
		
	 
		 $is_inserted = DB::table('seller_products')
                    ->insertGetId([
								'product_images' =>  $product_images,
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
								'LS_ID' =>  $lsid,
							]);
		if($is_inserted>0){
			
			
			
			
				if (array_key_exists('variations', $data) && isset($data['variations'])) {
					
				$arr2 = [];
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
					$sku = empty($arr2['product_sku']) ? '' : $arr2['product_sku'];
					$qty = empty($arr2['quantity']) ? '' : $arr2['quantity'];
					$price = empty($arr2['sale_price']) ? '' : $arr2['sale_price']; 
					$was_price = empty($arr2['list_price']) ? '' : $arr2['list_price']; 
					$opt = isset($arr2['options']) ? $arr2['options'] : null;
					$k=0; 
					$optarr = [];
					foreach($opt as $key => $val) {
						
						$optarr[$k] = $key.':'.$val;
						
						$k++;
				    }
				
				    
					$is_variation_inserted = DB::table('seller_products_variations')
                    ->insert([
								'product_id' =>  $product_id,
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
								
							]);
					
					$arr2= [];
				
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
	
	
	 public static function get_masterdatascript()
    {
		  $query  = DB::table('master_data')->select("*")->whereRaw("product_sub_header_1!='NULL' OR product_sub_desc_1 != 'NULL'  OR product_image_sub_1!='NULL'")->get(); 
		
			$a = [];
			foreach ($query as $product){
		
					$j=0;
					$arr = [];
					$desc_sub_arr = [];
					$jarr = [];
					$desc_sub = '';

					$arr[0]['header'] = $product->product_sub_header_1 ?? '' ;
					$arr[0]['desc'] = $product->product_sub_desc_1 ?? '' ;
					$arr[0]['image'] = $product->product_image_sub_1 ?? '' ;
					if($arr[0]['header']=='' && $arr[0]['desc']=='' && $arr[0]['image']==''){
						
						 $desc_sub_arr[0] = '';
					}
					else{
							$desc_sub_arr[0] = $arr[0];
					}
					

					$arr[1]['header'] = $product->product_sub_header_2 ?? '' ;
					$arr[1]['desc'] = $product->product_sub_desc_2 ?? '' ;
					$arr[1]['image'] = $product->product_image_sub_2 ?? '' ;
					
					if($arr[1]['header']=='' && $arr[1]['desc']=='' && $arr[1]['image']==''){
						
						 $desc_sub_arr[1] = '';
					}
					else{
							$desc_sub_arr[1] = $arr[1];
					}

					

					$arr[2]['header'] = $product->product_sub_header_3 ?? '' ;
					$arr[2]['desc'] = $product->product_sub_desc_3 ?? '' ;
					$arr[2]['image'] = $product->product_image_sub_3 ?? '' ;
					
					if($arr[2]['header']=='' && $arr[2]['desc']=='' && $arr[2]['image']==''){
						
						 $desc_sub_arr[2] = '';
					}
					else{
							$desc_sub_arr[2] = $arr[2];
					}
					
					

					$arr[3]['header'] = $product->product_sub_header_4 ?? '' ;
					$arr[3]['desc'] = $product->product_sub_desc_4 ?? '' ;
					$arr[3]['image'] = $product->product_image_sub_4 ?? '' ;
					
					if($arr[3]['header']=='' && $arr[3]['desc']=='' && $arr[3]['image']==''){
						
						 $desc_sub_arr[3] = '';
					}
					else{
							$desc_sub_arr[3] = $arr[3];
					}
					$j = 0;
					for($i=0;$i<4;$i++){
					  if($desc_sub_arr[$i]!=''){
							$jarr[$j] = $desc_sub_arr[$i];
							$j++;
					  }
					}				
					
					if(count($jarr)>0){
						$desc_sub = json_encode($jarr);
					}
			 
					DB::table('master_data')
                    ->where('id', $product->id)
                    ->update(['product_sub_details' => $desc_sub]);
					
            }
			
            
    }
}
