<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use PhpParser\Node\Expr\Variable;
use App\Models\SellerMapping;
use Auth;
use Exception;

// majorly writen for westelm products

class SellerProduct extends Model
{



	public static function save_sellerProduct($data)
	{

		$user_id = 0;
		$is_authenticated = Auth::check();
		$user = Auth::user();
		$user_id = $user->id;
		$brandname = '';
		$bnamefolder = 'brand';
		$brandid = '';
		$error = [];
		$a['status'] = true;

		$mode = isset($data['mode']) ? $data['mode'] : '';


		$query_brand  = DB::table('seller_brands')->select("*")->whereRaw("user_id=" . $user_id)->get();
		if (!empty($query_brand) && sizeof($query_brand) > 0) {
			$brandname = trim($query_brand[0]->value);
			$brandid = $query_brand[0]->id;
			$bnamefolder = str_replace(' ', '', $query_brand[0]->name);
		}
		if ($mode != 'edit') {

			if (isset($data['product_sku']) && $data['product_sku'] != 'null') {
				$product_sku =  str_replace(' ', '-', $data['product_sku']);
			} else {
				$randno = rand(1, 999999);
				$product_sku = '21' . $randno;
			}

			$querysku = DB::table('seller_products')->select(DB::raw('COUNT(id) as cnt_sku'))->where('product_sku', '=', $product_sku)->get();

			if ($querysku[0]->cnt_sku > 0) {
				$error[] = response()->json(['error' => 'Product Sku already exists', 'key' => 'product_sku'], 422);
				$a['status'] = false;
			}
		} else {
			$product_sku = $data['product_sku'];
		}
		if (isset($data['product_name']) && $data['product_name'] != 'null') {
			$product_name = $data['product_name'];
		} else {
			$product_name = '';
			$error[] = response()->json(['error' => 'Add a product name here', 'key' => 'product_name'], 422);
			$a['status'] = false;
		}

		if (isset($data['description']) && $data['description'] != 'null') {
			$product_description = $data['description'];
		} else {
			$product_description = '';
			$error[] = response()->json(['error' => 'Let customers know why they\'ll love your product!', 'key' => 'description'], 422);
			$a['status'] = false;
		}

		if (isset($data['features']) && $data['features'] != 'null') {
			$product_feature = $data['features'];
		} else {
			$product_feature = '';
			$error[] = response()->json(['error' => 'Share key highlights on your product.', 'key' => 'features'], 422);
			$a['status'] = false;
		}

		if (isset($data['assembly']) && $data['assembly'] != 'null') {
			$product_assembly = $data['assembly'];
		} else {
			$product_assembly = '';
		}

		if (isset($data['care']) && $data['care'] != 'null') {
			$product_care = $data['care'];
		} else {
			$product_care = '';
		}

		if (array_key_exists('style', $data) && isset($data['style'])) {
			$style = json_encode($data['style']);
		} else {
			$style = '';
		}

		if (isset($data['shape']) && $data['shape'] != 'null') {
			$shape = $data['shape'];
		} else {
			$shape = '';
		}

		if (array_key_exists('seats', $data) && isset($data['seats'])) {
			$seating = json_encode($data['seats']);
		} else {
			$seating = '';
		}

		if (isset($data['firmness']) && $data['firmness'] != 'null') {
			$firmness = $data['firmness'];
		} else {
			$firmness = '';
		}

		if (array_key_exists('country', $data) && isset($data['country'])) {
			$mfg_country = json_encode($data['country']);
		} else {
			$mfg_country = '';
		}

		if (isset($data['is_handmade']) && $data['is_handmade'] != 'null') {
			$is_handmade = $data['is_handmade'];
		} else {
			$is_handmade = '';
		}

		if (isset($data['sustainably_sourced']) && $data['sustainably_sourced'] != 'null') {
			$is_sustainable = $data['sustainably_sourced'];
		} else {
			$is_sustainable = '';
		}

		/*if (isset($data['shipping_type']) && $data['shipping_type'] != 'null') {
			$shipping_code = $data['shipping_type'];
		} else {
			$shipping_code = '';
			$error[] = response()->json(['error' => 'Please enter your selection for shipping type.', 'key' => 'shipping_type'], 422);
			$a['status'] = false;
		}*/
		if (isset($data['shipping_info']) && $data['shipping_info'] != 'null') {
			$shipping_code = $data['shipping_info']['shipping_type'];
			
			$process_time = $data['shipping_info']['process_time'];
			$process_time_type = $data['shipping_info']['process_time_type'];
			if($process_time_type=='weeks'){
				$process_time_type = 'w';
			}else{
				$process_time_type = 'd';
			}

			$ship_time = $data['shipping_info']['ship_time'];
			$ship_time_type = $data['shipping_info']['ship_time_type'];
			if($ship_time_type=='weeks'){
				$ship_time_type = 'w';
			}else{
				$ship_time_type = 'd';
			}
		} else {
			$shipping_code = '';
			$error[] = response()->json(['error' => 'Please enter your selection for shipping type.', 'key' => 'shipping_info'], 422);
			$a['status'] = false;
		}

		$lsid = '';

		if (array_key_exists('categories', $data) && isset($data['categories'])) {

			$lsarr = [];
			for ($i = 0; $i < (count($data['categories']) - 1); $i++) {
				if ($data['categories'][$i]['department'] != '' && $data['categories'][$i]['department'] != 'null') {

					$query = DB::table("mapping_core")
						->select(['LS_ID'])
						->where('dept_name_url', $data['categories'][$i]['department']);

					if ($data['categories'][$i]['category'] != '' && $data['categories'][$i]['category'] != 'null') {
						$query = $query->where('cat_name_url', $data['categories'][$i]['category']);
					}
					else{
						$query = $query->where('cat_name_url','');
					}
					if ($data['categories'][$i]['sub_category'] != '' && $data['categories'][$i]['sub_category'] != 'null') {
						$query = $query->where('cat_sub_url', $data['categories'][$i]['sub_category']);
					}
					else{
						$query = $query->where('cat_sub_url','');
					}
					
					
					$query = $query->get();

					foreach ($query as $row) {
						array_push($lsarr, $row->LS_ID);
					}
					$lsid = implode(",", $lsarr);
				}
			}
			
			  
		} else {

			$error[] = response()->json(['error' => 'Select at least one category where customers can find your product.', 'key' => 'categories'], 422);
			$a['status'] = false;
		}

		if (array_key_exists('dimensions', $data) && isset($data['dimensions'])) {
			$dimensions = json_encode($data['dimensions']);
		} else {
			$dimensions = '';
		}

		if (array_key_exists('colors', $data) && isset($data['colors'])) {
			$color = json_encode($data['colors']);
		} else {
			$color = '';
		}

		if (array_key_exists('materials', $data) && isset($data['materials'])) {
			$material = json_encode($data['materials']);
		} else {
			$material = '';
		}



		$variations = '[]';
		$product_images = '';
		$product_main_images = '';
		$quantity = '';
		$variations_count = 0;
		$datetime = date("Y-m-d H:i:s");
		$price = '';

		$has_variations = $data['has_variations'];
		if ($has_variations == true) {
			if (isset($data['variation_structure']) && $data['variation_structure'] != 'null') {
				$variations = json_encode($data['variation_structure']);
				$variations_count = count($data['variations']);
			}
		} else {
					
					if($mode == 'edit'){
						$query  = DB::table('seller_products')
						->where('submitted_id', $user_id)
						->where('product_sku', $data['product_sku'])
						->get();
						$variations = $query[0]->variations;
						$variations_count = 0;
					}
			
					if (isset($data['price']) && $data['price'] != 'null' && $data['price'] != '') {
						$price = $data['price'];
					} else {
						$price = '';
					}

					if (isset($data['quantity']) && $data['quantity'] != 'null' && $data['quantity'] != '') {
						$quantity = $data['quantity'];
					} else {
						$quantity = '';
					}
					if ($price == '' && $quantity == '') {
						$error[] = response()->json(['error' => 'Please enter your product price and quantity information.', 'key' => 'price_quantity'], 422);
						$a['status'] = false;
					}
		}


		if (array_key_exists('product_images', $data) && isset($data['product_images'])) {
			$upload_folder = '/var/www/html/seller/';
			if ($mode != 'edit') {

				$mode = 0777;
				@mkdir($upload_folder . $bnamefolder . "/img/", $mode, true);


				for ($i = 0; $i < count($data['product_images']); $i++) {

					$image_parts = explode(";base64,", $data['product_images'][$i]);
					$image_type_aux = explode("image/", $image_parts[0]);
					$image_type = $image_type_aux[1];
					$image_base64 = base64_decode($image_parts[1]);

					$image_name = time() . '-' . Utility::generateID() . '.' . $image_type;
					$uplaod =  file_put_contents($upload_folder . $bnamefolder . '/img/' . $image_name, $image_base64);
					$arr[$i] = '/seller/' . $bnamefolder . '/img/' . $image_name;
				}
				//return $uplaod;
				if ($uplaod) {
					$product_main_images = $arr[0];
					$product_images = json_encode($arr);
				} else
					$error[] = response()->json(['error' => 'image could not be uploaded. Please try again.'], 422);
			} else {

				for ($i = 0; $i < count($data['product_images']); $i++) {

					$imagedata = SellerProduct::is_base64_encoded($data['product_images'][$i]);
					//$arr[$i]['imagedata'] =  $imagedata ;
					if ($imagedata == 1) {

						$image_parts = explode(";base64,", $data['product_images'][$i]);
						$image_type_aux = explode("image/", $image_parts[0]);
						$image_type = $image_type_aux[1];
						$image_base64 = base64_decode($image_parts[1]);

						$image_name = time() . '-' . Utility::generateID() . '.' . $image_type;
						$uplaod =  file_put_contents($upload_folder . $bnamefolder . '/img/' . $image_name, $image_base64);
						$arr[$i] = '/seller/' . $bnamefolder . '/img/' . $image_name;
					} else {
						$imglink = substr($data['product_images'][$i], strrpos($data['product_images'][$i], '/') + 1);
						$arr[$i] = '/seller/' . $bnamefolder . '/img/' . $imglink;
					}
				}
				$product_main_images = $arr[0];
				$product_images = json_encode($arr);
			}

			for ($i = 0; $i < count($data['product_images_names']); $i++) {
				$imgnamearr[$i] = $data['product_images_names'][$i];
			}
		} else {
			$error[] = response()->json(['error' => 'Add atleast one image to showcase your product.', 'key' => 'product_images'], 422);
			$a['status'] = false;
		}


		$desc_sub = [];
		$datajson = '';


		if ($a['status']) {
			DB::beginTransaction();
			try {
				if ($mode != 'edit') {

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
							'ship_time' => $ship_time.$ship_time_type,
							'process_time' => $process_time.$process_time_type,
						]);
				} else {

					$is_inserted =  DB::table('seller_products')
						->where('product_sku', $data['product_sku'])
						->update([
							'product_images' =>  $product_images,
							'main_product_images' =>  $product_main_images,
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
							'shipping_code' => $shipping_code,
							'quantity' => $quantity,
							'variations_count' => $variations_count,
							'min_price' => $price,
							'max_price' => $price,
							'min_was_price' => $price,
							'max_was_price' => $price,
							'updated_date' => $datetime,
							'product_dimension' => $dimensions,
							'ship_time' => $ship_time.$ship_time_type,
							'process_time' => $process_time.$process_time_type, 
						]);
				}


				if ($is_inserted > 0) {
					
					if ($has_variations && array_key_exists('variations', $data) && isset($data['variations'])) {
						if ($mode == 'edit') {
							$delvar = DB::table('seller_products_variations')->where('product_id', $product_sku)->delete();
							//$delvar = DB::table('seller_products_variations')->where('product_id', $product_sku)->update(['status' =>'inactive']);
						}
						$arr2 = [];
						$min_price = 1000000;
						$max_price = 0;
						$min_was_price = 1000000;
						$max_was_price = 0;
						for ($i = 0; $i < count($data['variations']); $i++) {
							$arr2 = $data['variations'][$i];
							$variation_images = '';


							if (isset($arr2['image']) && $arr2['image'] != 'null') {
								$arr1 = [];
								for ($j = 0; $j < count($arr2['image']); $j++) {
									if (in_array($arr2['image'][$j], $imgnamearr)) {
										$pos = array_search($arr2['image'][$j], $imgnamearr);

										$arr1[$j] = $arr[$pos];
									}
								}
								//$variation_images = json_encode($arr1);
								$variation_images = implode(',',$arr1);
							}

							$product_id = $is_inserted;
							$status = $arr2['available'] == 1 ? 'active' : 'inactive';
							$name = empty($arr2['product_name']) ? '' : $arr2['product_name'];
							$sku = empty($arr2['product_sku']) ? $product_sku . '-00' . ($i + 1) : $arr2['product_sku'];
							$qty = empty($arr2['quantity']) ? '' : $arr2['quantity'];
							$was_price = empty($arr2['list_price']) ? 0 : $arr2['list_price'];
							$price = empty($arr2['sale_price']) ? $was_price : $arr2['sale_price'];

							if ($arr2['available'] == 1) {
								if ($min_price > $price) {
									$min_price = $price;
								}
								if ($max_price < $price) {
									$max_price = $price;
								}

								if ($min_was_price > $was_price) {
									$min_was_price = $was_price;
								}
								if ($max_was_price < $was_price) {
									$max_was_price = $was_price;
								}
							}

							$opt = isset($arr2['options']) ? $arr2['options'] : null;
							$k = 0;
							$optarr = [];
							$pname = '';
							foreach ($opt as $key => $val) {

								$optarr[$k] = $key . ':' . $val;
								$pname = $pname . ' ' . $val;

								$k++;
							}
							if ($name == '') {
								$name = $pname;
							}

							/*$skuexistcount = DB::table('seller_products_variations')->select(DB::raw('COUNT(id) as cnt'))->where('sku', '=', $arr2['product_sku'])->get();
							//return $skuexistcount;
							if($skuexistcount[0]->cnt>0){

								$is_variation_inserted = DB::table('seller_products_variations')
								->where('id', $arr2['product_sku'])
								->update([
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
									'updated_date' => $datetime,
									'image_path' => $variation_images,
								]);

							}	
							else{*/
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
									'image_path' => $variation_images,

								]);

							//}

							

							$arr2 = [];
						}
						
						$updateDB =  DB::table('seller_products')
							->where('product_sku', $product_sku)
							->update([
								'min_price' => $min_price,
								'max_price' => $max_price,
								'min_was_price' => $min_was_price,
								'max_was_price' => $max_was_price,
							]);
					}
					else{
						if ($mode == 'edit') {
							//$delvar = DB::table('seller_products_variations')->where('product_id', $product_sku)->delete();
							$delvar = DB::table('seller_products_variations')->where('product_id', $product_sku)->update(['status' =>'inactive']);
						}
					}



					$a['status'] = true;
					DB::commit();
                    (new SellerMapping())->map_seller_product_to_master_data($product_sku, $mode==='edit'); 

				} else {
					$a['status'] = false;
				}
			} catch (Exception $e) {
				DB::rollback();
				throw new Exception($e->getMessage());
			}

		}
		$a['errors'] = $error;

		return $a;
	}



	public static function get_sellerProductInfo()
	{

		// Get Variation Information

		$all_label = [];
		$query       = DB::table('seller_variations')->select(['var_ID', 'var_label', 'var_value', 'var_unit', 'var_type'])->get();

		$all_variation = [];
		foreach ($query as $row) {
			$all_variation['var_ID'] = $row->var_ID;
			$all_variation['var_label'] = $row->var_label;
			$all_variation['var_type'] = $row->var_type;
			$all_variation['options'] = [];

			if ($row->var_type == '1') { 
				$all_variation['options'] = (explode(",", $row->var_value));
			}
			if ($row->var_type == '3') { 
				$all_variation['options'] = (explode(",", $row->var_unit));
			}
			array_push($all_label, $all_variation);
		}


		//Get Shipping Information

		$query1       = DB::table('lz_ship_code')->whereNull('brand_id')->get();

		$all_shipping = [];
		foreach ($query1 as $row) {


			array_push($all_shipping, $row);
		}

		$a['all_label'] = $all_label;
		$a['all_shipping'] = $all_shipping;

		return $a;
	}


	public static function get_sellerProductList()
	{
		$user_id = 0;
		$is_authenticated = Auth::check();
		$user = Auth::user();
		$user_id = $user->id;
		//$user_id = 1093;
		$query       = DB::table('seller_products')
			->where('submitted_id', $user_id)
			->join("seller_brands", "seller_products.brand", "=", "seller_brands.value")
			->orderBy("updated_date", "DESC")
			->get();

		$all_products = [];
		foreach ($query as $row) {
			$row->variations = json_decode($row->variations);
			array_push($all_products, $row);
		}
		return $all_products;
	}

	public static function get_sellerProductDetails($sku)
	{
		$user_id = 0;
		$is_authenticated = Auth::check();
		$user = Auth::user();
		$user_id = $user->id;
		//$user_id = 1097;
		$query       = DB::table('seller_products')
			->where('submitted_id', $user_id)
			->where('product_sku', $sku)
			->join("seller_brands", "seller_products.brand", "=", "seller_brands.value")
			->get();

		$all_products = [];
		$all_products_var = [];
		$product_images = [];

		$product_images_decode = [];
		$catarrall = [];

		foreach ($query as $row) {

			$row->mfg_country = json_decode($row->mfg_country);
			$row->style = json_decode($row->style);
			$row->material = json_decode($row->material);
			$row->color = json_decode($row->color);
			$row->product_dimension = json_decode($row->product_dimension);
			$row->seating = json_decode($row->seating);
			if ($row->is_handmade == '1') {
				$row->is_handmade = 1;
			} else {
				$row->is_handmade = 0;
			}

			/************* Variation Start ******************/

			$variationarr = json_decode($row->variations);
			$variationOptionsArr = []; 
			foreach ($variationarr as $vararr) {
				$variationOptions['all_values'] = '';
				$queryvarattr  = DB::table('seller_variations')->select("*")->where("var_label", $vararr->attribute_name)->get();
				if(count($queryvarattr)>0){
					if ($queryvarattr[0]->var_type == 1) {
						$variationOptions['all_values'] = explode(',', $queryvarattr[0]->var_value);
					}

					if ($queryvarattr[0]->var_type == 3) {
						$variationOptions['all_values'] = explode(',', $queryvarattr[0]->var_unit);
					}
					$variationOptions['var_type'] = $queryvarattr[0]->var_type ;

				}
				else{
						$variationOptions['var_type'] = 2 ;
				}
				$variationOptions['attribute_name'] = $vararr->attribute_name;
				$variationOptions['selected_values'] = $vararr->attribute_options;
				array_push($variationOptionsArr, $variationOptions);
			}

			$row->variationOptions =  $variationOptionsArr;

			/************* Variation End ******************/

			/******************** Add Image Url Start  ******************************* */
			if ($row->product_images != '') {
				$row->main_product_images = 'https://www.lazysuzy.com' . $row->main_product_images;

				$product_images_decode = json_decode($row->product_images);


				foreach ($product_images_decode as $img) {
					$imgs = 'https://www.lazysuzy.com' . $img;
					array_push($product_images, $imgs);
				}
				$row->product_images = $product_images;
			}

			/******************** Add Image Url Start  ******************************* */

			/************* Get Category from LSID Start  ************** */

			$queryCat     = DB::table('mapping_core')
				->whereIn('LS_ID', explode(',', $row->LS_ID))
				->whereNotNull('dept_name_url')
				->groupBy('dept_name_url')
				->get();


			foreach ($queryCat as $catdetails) {

				$catarr['department'] = $catdetails->dept_name_url;
				$catarr['category'] = $catdetails->cat_name_url;
				$catarr['sub_category'] = $catdetails->cat_sub_url;

				array_push($catarrall, $catarr);
			}


			$row->categories = array_reverse($catarrall);

			/************* Get Category from LSID End  ************** */
			/************* Get Shipping Info Start ****************** */
			

			$shippingarr = []; 
			$shippingarr['shipping_type'] = $row->shipping_code;

			if($row->ship_time!=''){ 
				$shippingarr['ship_time'] = substr(trim($row->ship_time), 0, -1);
				$shippingarr['ship_time_type'] = substr(trim($row->ship_time), -1)=='w'?'weeks':'business_days';
			}
			if($row->process_time!=''){ 
				$shippingarr['process_time'] = substr(trim($row->process_time), 0, -1);
				$shippingarr['process_time_type'] = substr(trim($row->process_time), -1)=='w'?'weeks':'business_days';
			}

			$row->shipping_info = $shippingarr;
			/************* Get Shipping Info Start ****************** */
			/********************* Get Variation Details Start  ******************** */

			$row->variations = json_decode($row->variations);
			$all_products_var = [];
			//if($row->variations_count>0){

				$query1     = DB::table('seller_products_variations')
					->where('product_id', $sku)
					->get()->toArray();
 
				if (isset($query1)) {

					foreach ($query1 as $row1) {
						// Get attribute Option Here 

						$option = [];
						if ($row1->attribute_1 != '') {
							$attr = explode(":", $row1->attribute_1);
							$key = $attr[0];
							$val = $attr[1];
							$option[$key] = $val;
						}
						if ($row1->attribute_2 != '') {
							$attr = explode(":", $row1->attribute_2);
							$key = $attr[0];
							$val = $attr[1];
							$option[$key] = $val;
						}
						if ($row1->attribute_3 != '') {
							$attr = explode(":", $row1->attribute_3);
							$key = $attr[0];
							$val = $attr[1];
							$option[$key] = $val;
						}
						if ($row1->attribute_4 != '') {
							$attr = explode(":", $row1->attribute_4);
							$key = $attr[0];
							$val = $attr[1];
							$option[$key] = $val;
						}
						if ($row1->attribute_5 != '') {
							$attr = explode(":", $row1->attribute_5);
							$key = $attr[0];
							$val = $attr[1];
							$option[$key] = $val;
						}
						if ($row1->attribute_6 != '') {
							$attr = explode(":", $row1->attribute_6);
							$key = $attr[0];
							$val = $attr[1];
							$option[$key] = $val;
						}


						$row1->options = $option;


						$varimgs = '';
						$optionimg = [];



						if ($row1->image_path != "") {
							$var_images_decode = explode(',',$row1->image_path); //json_decode($row1->image_path);
							for ($i = 0; $i < count($var_images_decode); $i++) {
								$optionimg[$i] = "https://www.lazysuzy.com" . $var_images_decode[$i];
							}
							$row1->image_path = [];
							$row1->image_path = $optionimg;
						}

						array_push($all_products_var, $row1);
					}
				}

			//}


			$row->variations_details = $all_products_var;


			/********************* Get Variation Details End  ******************** */




			// array_push($all_products, $row);
			return json_encode($row);
		}
		//return $all_products;

	}

	public static function is_base64_encoded($data)
	{
		if (strpos($data, 'lazysuzy') == false) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}