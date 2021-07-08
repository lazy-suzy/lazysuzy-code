<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Auth;

class Wishlist extends Model
{
    public static function get_whishlist($is_board_view = false)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $products = DB::table("user_wishlists")
                ->join("master_data", "master_data.product_sku", "=", "user_wishlists.product_id")
                ->join("master_brands", "master_data.site_name", "=", "master_brands.value");

            if ($is_board_view)
                $products->where("master_data.image_xbg_processed", 1);

            $products = $products->where("user_wishlists.user_id", $user->id)
                ->where("user_wishlists.is_active", 1)
                ->get()->toArray();

            $products_structured = [];
            foreach ((object) $products as $prod) {
                $variations = Product::get_variations($prod, null, true);

                // inject inventory details
                $product_inventory_details = Inventory::get_product_from_inventory($user, $prod->product_sku);
                $prod = (object) array_merge($product_inventory_details, (array) $prod);

                array_push($products_structured, Product::get_details($prod, $variations, true, true));
            }

            return [
                "products" => $products_structured
            ];
        }
    }
    public static function mark_favourite_product($sku)
    {
        $result = [];
        $result["status"] = 0;

        if (Auth::check()) {
            $result["type"] = "permanent";

            $user = Auth::user();
            $wishlist_product_count = DB::table("user_wishlists")
                ->where("user_id", $user->id)
                ->where("product_id", $sku)
                ->get();

            $get_product_details = DB::table("master_data")
                ->where("product_sku", $sku)
                ->get();    

            if (count($wishlist_product_count) == 0) {
                $id = DB::table("user_wishlists")
                    ->insertGetId([
                        "user_id" => $user->id,
                        "product_id" => $sku,
                        "min_price" => $get_product_details[0]->min_price,
                        "max_price" => $get_product_details[0]->max_price,
                        "min_was_price" => $get_product_details[0]->min_was_price,
                        "max_was_price" => $get_product_details[0]->max_was_price,

                    ]);

                if ($id != null) {
                    $result["status"] = 1;
                    $result["msg"] = "Product marked favourite successfully";
                }
            } else {

                // update is_active to 1 by default
                DB::table("user_wishlists")
                    ->where("user_id", $user->id)
                    ->where("product_id", $sku)
                    ->update([
                                "is_active" => 1,
                                "min_price" => $get_product_details[0]->min_price,
                                "max_price" => $get_product_details[0]->max_price,
                                "min_was_price" => $get_product_details[0]->min_was_price,
                                "max_was_price" => $get_product_details[0]->max_was_price,  
                ]);
                $result["msg"] = "Product already marked favourite";
            }
        } else {
            $result["type"] = "temporary";
            // handle no login requests
        }

        return $result;
    }

    public static function unmark_favourite_product($sku)
    {
        $result = [];
        if (Auth::check()) {
            $user = Auth::user();
            $update = DB::table("user_wishlists")
                ->where("user_id", $user->id)
                ->where("product_id", $sku)
                ->update(["is_active" => 0]);
            if ($update) {
                return [
                    "status" => true,
                    "msg" => "Un-mark Success"
                ];
            } else {
                return [
                    "status" => false,
                    "msg" => json_encode($update)
                ];
            }
        } else {
            // handle no login requests
            return [];
        }
        return $result;
    }

    public static function is_wishlisted($user, $sku)
    {

        $user_id  = $user->id;
        $product_sku = $sku;

        $q =  DB::table("user_wishlists")
            ->where("product_id", $product_sku)
            ->where("user_id", $user_id)
            ->where("is_active", 1);

        return $q->exists();
    }

    /*public function get_wishlist_price_change_sku()
    {
        $arr = [];
        
        $whishlist_sku = $this->db->select("*")
            ->from('user_wishlists')
            ->where("is_active", 1)
            ->get()
            ->result();

        foreach ($whishlist_sku as $sku) {
            $product_sku = $sku->product_id; 

            $data = $this->db->select('*')
                ->from('master_data')
                ->where('product_sku', $product_sku)
                ->get()->result_array();
            
            if (sizeof($data) > 0){
                $data = $data[0];
                if($data["min_price"]==$sku->min_price && $data["max_price"]==$sku->max_price && $data["min_was_price"]==$sku->min_was_price && $data["max_was_price"]==$sku->max_was_price){
                    //do nothing
                }
                else{
                        array_push($arr,$sku);
                }
            }
        } 
        return $arr;   
    }*/

    /*public function merge_product_price_history(){
        $product_rows = $this->db->select(['product_sku', 'min_price', 'min_price', 'min_was_price', 'max_was_price'])
            ->from('master_data')
            ->where("product_status", 'active')
            ->get()
            ->result();
        
        if(sizeof($product_rows)>0){
            foreach ($product_rows as $row) {
                $this->db->from('product_price_history');
                $this->db->where('product_sku', $row->product_sku);
                $cnt = $this->db->count_all_results();
                if($cnt>0){
                            $history_data = $this->db->query("SELECT * FROM product_price_history WHERE product_sku = '".$row->product_sku."' AND end_date IS NULL")->result();
                            $history_data = $history_data[0];

                            if($history_data->min_price == $row->min_price && $history_data->max_price == $row->max_price){
                                //do nothing
                            }
                            else{
                                   // Update existing row end_date with yesterday date
                                    $olddate = date('Y-m-d',strtotime("-1 days"));
                                    $this->db->set(['end_date'=> $olddate])->where('product_sku', $row->product_sku)->where('id', $history_data->id)->update('product_price_history');

                                    // Then insert new row with the updated price and end date null
                                    $datetime = date("Y-m-d H:i:s");

                                    $data_fields = array(
                                        'product_sku'   => $row->product_sku,
                                        'min_price'     => $row->min_price,
                                        'max_price'     => $row->max_price,
                                        'min_was_price' => $row->min_was_price,
                                        'max_was_price' => $row->max_was_price,
                                        'from_date'     => $datetime
                                    );

                                    $this->db->insert('product_price_history', $data_fields);
                            
                            }
                    

                }
                else{
                        $datetime = date("Y-m-d H:i:s");

                        $data_fields = array(
                            'product_sku'   => $row->product_sku,
                            'min_price'     => $row->min_price,
                            'max_price'     => $row->max_price,
                            'min_was_price' => $row->min_was_price,
                            'max_was_price' => $row->max_was_price,
                            'from_date'     => $datetime
                        );

                        $this->db->insert('product_price_history', $data_fields);
                }

            }
        }
        
    }*/
}
