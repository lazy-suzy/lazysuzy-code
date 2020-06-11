<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Auth;

class Wishlist extends Model {
    public static function get_whishlist($is_board_view = false) 
    {
        if(Auth::check()) {
            $user = Auth::user();
            $products = DB::table("user_wishlists")
                ->join("master_data", "master_data.product_sku", "=", "user_wishlists.product_id")
                ->join("master_brands", "master_data.site_name", "=", "master_brands.value");

            if($is_board_view) 
                $products->where("master_data.image_xbg_processed", 1);

            $products = $products->where("user_id", $user->id)
                ->where("is_active", 1)
                ->get()->toArray();
                
            $products_structured = [];
            foreach((object) $products as $prod) {
                $variations = Product::get_variations($prod, null, true);

                // inject inventory details
                $product_inventory_details = Product::get_product_from_inventory($user, $prod->product_sku);
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

            if (count($wishlist_product_count) == 0) {
                $id = DB::table("user_wishlists")
                    ->insertGetId([
                        "user_id" => $user->id,
                        "product_id" => $sku
                    ]);

                if ($id != null) {
                    $result["status"] = 1;
                    $result["msg"] = "Product marked favourite successfully";
                }
            } else {
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
                    "msg" => "Unmark Success"
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
}