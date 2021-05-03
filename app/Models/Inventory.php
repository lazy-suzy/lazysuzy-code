<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Inventory extends Model
{
    private static $inventory_table = "lz_inventory";
    private static $cart_table = 'lz_user_cart';
    protected $table = 'lz_inventory';

    /**
     * This will return Product info. for the products added in the cart.
     * This will only work for products that are parent SKUs, for varaitions
     * we follow a different procedure.
     *
     * @return List 
     */
    public static function get()
    {

        return DB::table(Inventory::$inventory_table)
            ->select(
                Inventory::$inventory_table . '.product_sku',
                'master_data.product_name',
                Inventory::$inventory_table . '.price',
                Inventory::$inventory_table . '.quantity as count',
                DB::raw('concat("https://www.lazysuzy.com", master_data.main_product_images) as image'),
                'master_data.product_description',
                'master_data.reviews',
                'master_data.rating',
                'master_brands.name as site'
            )
            ->join("master_data", "master_data.product_sku", "=", Inventory::$inventory_table . ".product_sku")
            ->join("master_brands", "master_data.site_name", "=", "master_brands.value")
            ->where(Inventory::$inventory_table . '.is_active', 1)
            ->get()
            ->toArray();
    }

    /* 
        this will return an object that will contain all the info regarding
        product from inventory. This function takes in account for the items 
        already present in the user cart and calculates the right amount 
        of products that can be added in the cart on next attempt 
    */
    public static function get_product_from_inventory($user, $sku)
    {

        $res = [];
        $res['in_inventory'] = false;
        $res['inventory_product_details'] = null;
        $product_count_remaining = 0;
        $is_low = false;

        // we take the product count already present in the users cart
        // and then product object from the inventory
        if ($user != NULL) {
            $items_in_cart = DB::table(Inventory::$cart_table)
                ->where('user_id', $user->id)
                ->where('is_active', 1)
                ->where('product_sku', $sku)
                ->get()->count();

            $inventory_prod = Inventory::join(
                Config::get('tables.shipping_codes'),
                Config::get('tables.shipping_codes') . ".code",
                "=",
                Config::get('tables.inventory') . ".ship_code"
            )->where(Config::get('tables.inventory') . '.product_sku', $sku)
                ->where(Config::get('tables.inventory') . '.is_active', 1)
                ->get();
return $inventory_prod[0];
            if (isset($inventory_prod[0])) {
                $product_count_remaining = $inventory_prod[0]->quantity - $items_in_cart;

                // is_low needs to be set to true if quantity is less than or equal to 5
                $is_low = $inventory_prod[0]->quantity <= 5;

                $res['in_inventory'] = true;
                $res['inventory_product_details'] = [
                    'price' => Utility::rm_comma($inventory_prod[0]->price),
                    'was_price' => Utility::rm_comma($inventory_prod[0]->was_price),
                    'count' => $product_count_remaining,
                    'message' => $inventory_prod[0]->message,
                    'is_low' => $is_low,
                    'shipping_code' => $inventory_prod[0]->ship_code,
                    'shipping_desc' => $inventory_prod[0]->description
                ];
            }
        }

        return $res;
    }

    /**
     * This will return a SKU keyed list of all the products that are
     * in the inventory
     *
     * @return void
     */
    public static function get_product_list()
    {
        $rows = Inventory::all();
        $products = [];
        foreach ($rows as $row) {
            $products[$row->product_sku] = $row;
        }

        return $products;
    }
}
