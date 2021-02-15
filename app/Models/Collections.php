<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Collections extends Model
{

    public static function get_collections($sku, $collection_key, $brand) {
        $collections = [];
        if(!isset($collection_key) || strlen($collection_key) == 0)
            return $collections;
        
        $to_select = ["product_sku", "product_name","product_status", "site_name", "brand", "main_product_images", "price", "was_price"];
        $rows = DB::table(Config::get('tables.master_table'))
                        ->where("collection", $collection_key)
                        ->where("brand", $brand)
                        ->where("product_status", "active")
                        ->where("product_sku" , "!=", $sku)
                       ->get()->toArray();
        
        $user = Auth::user();
        foreach($rows as $row) {
            $row = (array) $row;
            $inventory_details = Inventory::get_product_from_inventory($user, $row['product_sku']);
          
            $collection_block = [
                "in_inventory" => $inventory_details['in_inventory'],
                "inventory_product_details" => $inventory_details['inventory_product_details'],
                "image" => $row['main_product_images'],
                "sku" => $row['product_sku'],
                "link" => "/product/" . $row['product_sku'],
                "price" => $row['price'],
                "was_price" => $row['was_price'],
                "name" => $row['product_name']
            ];
            $collections[] = $collection_block;
        }   
        
        return $collections;
    }  

    /**
     * GET /api/collections?collection=xx
     * Send details for xx collection via listed endpoint
     *
     * @param [type] $collection
     * @return collection_details 
     */
    public static function get_collection_details($collection) {

		$arr = [];
        $collection_table = Config::get('tables.collections'); 
        $collection_detail_count = Config::get('tables.collection_detail_count');
        $collection_details = [];
        $row = DB::table($collection_table)->where('value', $collection)->get();

        if(sizeof($row) == 0) 
            return $collection_details;

        $row = $row[0];
        $collection_details['name'] = $row->name;
        $collection_details['head_description'] = $row->desc_header;
        $collection_details['cover_details'] = [
            'image' => env('APP_URL') . $row->image_cover,
            'description' => $row->desc_cover
        ];
        $collection_details['sub_details'] = [];
		
		foreach(json_decode($row->desc_sub) as $desc_sub){
		
			$desc_sub->image =  env('APP_URL') . $desc_sub->image;
			array_push($arr,$desc_sub);
		
		}
		// $collection_details['sub_details'] = json_decode($row->desc_sub);
		 $collection_details['sub_details'] = $arr;

        /*for($i = 1; $i <= $collection_detail_count; $i++) {
            $desc_key = 'desc_sub_' . $i;
            $img_key = 'image_sub_' . $i;


            if(strlen($row->$img_key) > 0 && strlen($row->$desc_key) > 0) {
                $collection_details['sub_details'][] = [
                    'image' => env('APP_URL') . $row->$img_key,
                    'description' => $row->$desc_key
                ];
            }
            
        }*/

        return $collection_details;
    }


    /**
     * List of all available collections
     *
     * @return list_of_collections
     */
    public static function get_collection_list() {
        $collection_table = Config::get('tables.collections'); 
        $collection_list = [];
        $to_select = ["name", "value", "brand"];
        $rows = DB::table($collection_table)->select($to_select)
            ->where('is_active', 1)->get();

        foreach($rows as $row) {
            $collection_list[] = [
                "name" => $row->name . " x " . $row->brand,
                "value" => $row->value
            ];
        }

        return $collection_list;
    }
    
    /**
     * retunrn all the LSIDs that are there for the available collection options
     *
     * @param [type] $all_filters
     * @return array
     */
    public static function get_LSIDs($all_filters) {

        if(!isset($all_filters['collection']) || sizeof($all_filters['collection']) == 0)
            return [];
        
        $ls_ids = DB::table(Config::get('tables.master_table'))
            ->select('LS_ID')
            ->whereRaw('collection REGEXP "' . implode("|", $all_filters['collection']) . '"')
            ->get();

        $collection_LSIDs = [];

        foreach($ls_ids as $id) {
            $arr = explode(",", $id->LS_ID);
            foreach($arr as $id_a) {
                if(strlen($id_a) > 0 && !in_array($id_a, $collection_LSIDs)) {
                    $collection_LSIDs[] = $id_a;
                }
            } 
        }

        return $collection_LSIDs;
    }
	
	public static function get_all_collection_with_count(){
		 $arr = [];
		
		$sql = DB::table('master_data') 
				->select(DB::raw('count(product_sku) as product_count'))
				->where('collection', '!=', 'NULL')
				->groupBy('collection')				
				->get();
				
		foreach($sql as $data) { 
            array_push($arr,$data);
        }
		return $arr;	 
	
	}
}
