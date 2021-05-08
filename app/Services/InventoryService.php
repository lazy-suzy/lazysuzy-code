<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

/**
 * Manage Inventory Functions
 * Create, Update
 * @author Jatin Parmar <jatinparmar96@gmail.com>
 */
class InventoryService{
    // Define all private Fields
    private $productsToInsert;

    //Table name of inventory
    const TABLE_NAME = 'lz_inventory';

    function __construct(){

    }

    /**
     * Insert product
     * @param array $product
     * @return void
     */
    public function insert($product)
    {
        DB::table(self::TABLE_NAME)->insert($product);
    }


    /**
     *
     * @param array $products
     * @return void
     */
    public function update($products)
    {
        foreach ($products as $product)
        {
          DB::table(self::TABLE_NAME)->where('product_sku',$product['product_sku'])->update($product);
        }

    }

    /**
     * Delete product
     * @param array $product
     * @return void
     */
    public function delete($products)
    {
        foreach ($products as $product)
        {
          DB::table(self::TABLE_NAME)->where('product_sku',$product['product_sku'])->delete();
        }
    }

    /**
     * Inactive Active  product
     * @param array $product
     * @return void
     */
    public function change_status($product_sku)
    { 
          DB::table(self::TABLE_NAME)->where('parent_sku',$product_sku)->update(['is_active'=>'0']);
    
    }
}
