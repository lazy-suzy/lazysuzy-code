<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use PhpParser\Node\Expr\Variable;
use Auth;

// majorly writen for westelm products

class Variations extends Model
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


    /**
     * returns a string after striping all the `-`
     * and HTML tags (if any) 
     *
     * @param string $text
     * @return string
     */
    public static function sanitize($text)
    {
        $text = preg_replace("/-/", " ", $text);
        return strip_tags($text);
    }

    /**
     * will explode the string on `:` and 
     * return string before the colon 
     *
     * @param string $attr_str
     * @return string || NULL
     */
    public static function get_attr_value($attr_str)
    {
        $str_exp = explode(":", $attr_str);
        return isset($str_exp[1]) ? $str_exp[1] : null;
    }

    public static function get_variations($sku)
    {
        $filters = [];
        $cols = [
            "product_id AS product_sku",
            "sku AS variation_sku",
            "name",
            "price",
            "was_price",
            DB::raw('CONCAT("' . Product::$base_siteurl . '", image_path) as image'),
            DB::raw('CONCAT("' . Product::$base_siteurl . '", swatch_image_path) as swatch_image'),
            "swatch_image_path",
            "attribute_1",
            "attribute_2",
            "attribute_3",
            "attribute_4",
            "attribute_5",
            "attribute_6"
        ];


        $master_prod = DB::table("master_data")
            ->where("product_sku", $sku)
            ->get();

        $query = DB::table("westelm_products_skus")
            ->select($cols)
            ->distinct('swatch_image')
            ->where('product_id', $sku)
            ->whereRaw('LENGTH(swatch_image) > 0');

        if (in_array($master_prod[0]->site_name, ['pier1', 'cb2', 'nw'])) {
            return [
                "main_image" => Product::$base_siteurl . $master_prod[0]->main_product_images,
                "variations" => Product::get_variations($master_prod[0]),
                "filters" => null,
                //"raw_rseults" => $query->get()
            ];
        }

        
        foreach ($_GET as $key => $value) {
            $query = $query->where($key, 'like', '%' . Variations::sanitize($value) . '%');
            array_push($filters, [$key => Variations::sanitize($value)]);
        }


        $variations = $query->get();

        // handle for - if any product has empty swatch image, then include all the entries.

        $query = DB::table("westelm_products_skus")
            ->select($cols)
            ->where('product_id', $sku)
            ->whereRaw('LENGTH(swatch_image) = 0')
            ->get();

        
        $variations->merge($query);
        $variations = $variations->all();

        $filters = [];
        $products = [];

        $filter_values_unique = [];
        if (isset($master_prod[0])) {
            foreach ($variations as $variation) {
                $product = [];
                $col = "attribute_";

                // checking if swatch image col has a path or not

                // load basic details about the product
                $product = [
                    "product_sku" => $variation->product_sku,
                    "variation_sku" => $variation->variation_sku,
                    "name" => $variation->name,
                    "price" => round($variation->price),
                    "image" => $variation->image,
                    "swatch_image" => strlen($variation->swatch_image_path) > 0 ? $variation->swatch_image : null
                ];

                // will have to remove this for loop and replace it with 
                // get_filter_content method 
                for ($i = 1; $i <= 6; $i++) {
                    $col_name = $col . $i;

                    $str_exp = explode(":", $variation->$col_name);
                    if (isset($str_exp[0]) && isset($str_exp[1])) {
                        // $filter_key = Product::get_filter_key($str_exp[0]);

                        $filter_key = $col_name;

                        // load attr details for product
                        $product[$filter_key] = [
                            "label" => Product::get_filter_label($str_exp[0]),
                            "name" => urldecode($str_exp[1]),
                            "value" => (strtolower(preg_replace("/[\s]+/", "-", urldecode($str_exp[1]))))
                        ];

                        if (!isset($filter_values_unique[$filter_key]))
                            $filter_values_unique[$filter_key] = [];

                        if (!in_array($str_exp[1], $filter_values_unique[$filter_key])) {
                            array_push($filter_values_unique[$filter_key], $str_exp[1]);
                        }
                    }
                }

                array_push($products, $product);
            }


            //echo "<pre>" . print_r($filter_values_unique, true);
            $all_filters = Product::get_all_variation_filters($sku);

            foreach ($all_filters as $all_filter_key => $filter) {
                $found = false;
                foreach ($filter as $f) {
                    $found = false;
                    //echo "ALL FITER KEY: " . $all_filter_key . "<BR>";
                    if (isset($filter_values_unique[$all_filter_key])) {
                        foreach ($filter_values_unique[$all_filter_key] as $flt_name) {
                            //echo $flt_name . " == " . $f["name"] . "<br>";
                            if ($flt_name == $f["name"]) {
                                $found = true;
                                break;
                            }
                        }

                        if (!isset($filters[$all_filter_key])) {
                            $filters[$all_filter_key] = [];
                        }

                        array_push($filters[$all_filter_key], [
                            "label" => $f["label"],
                            "name" => $f["name"],
                            "value" => $f["value"],
                            "enabled" => $found
                        ]);
                    }
                }
            }
            // return ;
            $filters_struct = [];
            foreach ($filters as $filter_key => $filter) {
                $data = [];
                foreach ($filter as $flt) {
                    if ($flt["enabled"]) {
                        array_push($data, [
                            "name"  => $flt["name"],
                            "value" => $flt["value"],
                            "enabled" => $flt["enabled"],
                            "in_request" => in_array($flt["value"], $_GET)
                        ]);
                    }
                }
                $filters_struct[$filter_key] = [
                    "label" => $filters[$filter_key][0]["label"],
                    "options" => $data
                ];
                /* array_push($filters_struct[$filter_key], [
                    "label" => $filters[$filter_key][0]["label"],
                    "key" => $filter_key,
                    "options" => $data
                ]); */
            }

            return [
                "main_image" => Product::$base_siteurl . $master_prod[0]->main_product_images,
                "variations" => $products,
                "filters" => $filters_struct,
                //"raw_rseults" => $query->get()
            ];
        }

        return ["error" => "No Product with SKU " . $sku . " found."];
    }

    /**
     * use this to search elements inside multi-dim
     * array copied from PHP Docs. extends (in a way) in_array()
     *
     * @param string $elem
     * @param array $array
     * @return void
     */
    public static function in_multiarray($elem, $array)
    {
        while (current($array) !== false) {
            if (current($array) == $elem) {
                return true;
            } elseif (is_array(current($array))) {
                if (Variations::in_multiarray($elem, current($array))) {
                    return true;
                }
            }
            next($array);
        }
        return false;
    }

    public static function get_filter_content($data_with_attr)
    {

        $filter_values_unique = [];
        foreach ($data_with_attr as $data) {
            for ($i = 1; $i <= 6; $i++) {
                $col = "attribute_" . $i;

                $str_exp = explode(":", $data->$col);
                if (isset($str_exp[0]) && isset($str_exp[1])) {
                    //$filter_key = Product::get_filter_key($str_exp[0]);
                    $filter_key = $col;
                    if (!isset($filter_values_unique[$filter_key]))
                        $filter_values_unique[$filter_key] = [];

                    if (!Variations::in_multiarray($str_exp[1], $filter_values_unique[$filter_key])) {
                        array_push($filter_values_unique[$filter_key], [
                            "label" => Product::get_filter_label($str_exp[0]),
                            "name" => $str_exp[1],
                            "value" => preg_replace("/[\s]+/", "-", strtolower($str_exp[1]))
                        ]);
                    }
                }
            }
        }

        $filters_struct = [];
        foreach ($filter_values_unique as $filter_key => $filter) {
            $data = [];
            foreach ($filter as $flt) {

                array_push($data, [
                    "name"  => $flt["name"],
                    "value" => $flt["value"],
                    "enabled" => true,
                    "in_request" => in_array($flt["value"], $_GET)
                ]);
            }
            $filters_struct[$filter_key] = [
                "label" => $filter_values_unique[$filter_key][0]["label"],
                "options" => $data
            ];
            /* array_push($filters_struct[$filter_key], [
                    "label" => $filters[$filter_key][0]["label"],
                    "key" => $filter_key,
                    "options" => $data
                ]); */
        }

        return $filters_struct;
    }

    public static function get_swatch_filter($sku)
    {
        $swatch_url = urldecode(Input::get('swatch'));
        $cols = [
            "attribute_1",
            "attribute_2",
            "attribute_3",
            "attribute_4",
            "attribute_5",
            "attribute_6",
        ];

        $rows = DB::table('westelm_products_skus')
            ->select($cols)
            ->where("swatch_image_path", $swatch_url)
            ->where("product_id", $sku)
            ->get();
        $main_img = DB::table('master_data')
            ->select('main_product_images')
            ->where("product_sku", $sku)
            ->get();

        return [
            "main_image" => Variations::$base_siteurl .  $main_img[0]->main_product_images,
            "filters" => Variations::get_filter_content($rows)
        ];
    }
	
	public static function get_seller_variation_label(){
		 
        $all_label = [];
        $query       = DB::table('seller_variations')->select(['var_ID','var_label','var_value','var_unit', 'var_type'])->get(); 
		
		$all_variation = [];
		foreach ($query as $row){
			$all_variation['var_ID'] = $row->var_ID;
			$all_variation['var_label'] = $row->var_label;
			$all_variation['var_type'] = $row->var_type;
			$all_variation['options'] = [];
			
			if($row->var_type == '1') { 
				$all_variation['options'] = (explode(",",$row->var_value));
			}
			if($row->var_type == '3') { 
				$all_variation['options'] = (explode(",",$row->var_unit));
			}
            array_push($all_label, $all_variation);
	    } 
		
		return $all_label; 
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
