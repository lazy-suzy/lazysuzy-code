<?php

namespace App\Models;

use App\Services\InventoryService;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Map Seller Products to Master Data format.
 *
 *
 *
 */
class SellerMapping
{
    /**
     * Initialize Inventory Service Varialble
     */
    private $inventoryService;

    /**
     * This variable will store information which action to perform. `Insert` or `update`
     */
    private $edit = false;

    private static $seller_variations_table = 'seller_products_variations';
    /**
     * An array of all the fields from `seller_products` table that needs to be mapped to `master_data` table
     */
    private static $master_data_fields = [
        'serial',
        'LS_ID',
        'product_sku',
        'product_status',
        'brand',
        'product_name',
        'product_description',
        'product_feature',
        'product_dimension',
        'product_assembly',
        'product_care',
        'min_price',
        'max_price',
        'min_was_price',
        'max_was_price',
        'product_images',
        'main_product_images',
        'color',
        'material',
        'shape',
        'style',
        'seating',
        'firmness',
        'mfg_country',
        'designer',
        'is_handmade',
        'is_sustainable',
        'variations_count',
        'site_name'
    ];
    function __construct()
    {
        $this->inventoryService = new InventoryService();
    }

    /**
     * Map Seller Product data to master_data and insert it
     * @param string $product_sku  `product_sku` column in SellerProductsTable
     * @param bool $should_update true for updating sku already in table
     * @return void  //for now
     *
     */
    public function map_seller_product_to_master_data($product_sku, $should_update = false)
    {
        // Get the product from SellerProducts Table
        $seller_product = SellerProduct::where('product_sku', $product_sku)->first();

        // Update edit global field
        $this->edit = $should_update;

        // Apply Mapping transformations
        $seller_product->product_images = implode(',', json_decode($seller_product->product_images) ?? []);
        $seller_product->color = implode(',', json_decode($seller_product->color) ?? []);
        $seller_product->material = implode(',', json_decode($seller_product->material) ?? []);
        $seller_product->style = implode(',', json_decode($seller_product->style) ?? []);
        $seller_product->mfg_country = implode(',', json_decode($seller_product->mfg_country) ?? []);
        $seller_product->seating = implode(',', json_decode($seller_product->seating) ?? []);
        $seller_product->site_name = $seller_product->brand;
        $this->insert_or_update_master_data($seller_product);
    }

    /**
     *  Map the given seller product to master_data Table
     *  Handles both insert and update
     * @param SellerProduct $seller_product
     *
     */
    protected function insert_or_update_master_data(SellerProduct $seller_product)
    {

        DB::beginTransaction();

        try {
            if ($seller_product->variations_count > 0) {
                $this->map_variations_to_inventory($seller_product);
            } else {
                $this->map_product_to_inventory($seller_product);
            }

            // If edit retrieve master_product from the table, else create a new one.
            if ($this->edit) {
                $master_product = Product::where('product_sku', $seller_product->product_sku)->first();
            } else {
                $master_product = new Product();
            }

            $fields = $seller_product->only(self::$master_data_fields);
            $master_product->fill($fields);
            $master_product->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw new Exception($e->getMessage());
        }
    }

    private function map_variations_to_inventory($product)
    {
        $variations = DB::table(self::$seller_variations_table)->where('product_id', $product->product_sku)->get();
        $items = $this->create_inventory_items($variations, $product);
        $this->insert_or_update_inventory($items);
    }


    /**
     * Save Product in inventory without variations.
     * @param SellerProduct $product - Product to be saved
     */
    private function map_product_to_inventory($product)
    { 
        $items = [];
        $items[] = [
            'product_sku' => $product->product_sku,
            'price' => $product->min_price>0 ? $product->min_price : NULL,
            'brand' => $product->brand,
            'ship_code' => $product->shipping_code,
            'was_price' => $product->min_was_price>0 ? $product->min_was_price : NULL,
            'quantity' => $product->quantity>0 ? $product->quantity : NULL,
            'is_active' => $product->product_status=='active'?'1':'0',
        ];
        $this->insert_or_update_inventory($items);
    }

    /**
     * Create relative representation of how variations will be stored in table
     * @param Collection $variations - Variations to map
     * @param SellerProduct $product - Product to which variations belong to.
     * @return array $items - Mapped Items
     */
    private function create_inventory_items($variations, $product)
    {
        $items = $variations->map(function ($variation) use ($product) {
            return [
                'parent_sku' => $variation->product_id,
                'product_sku' => $variation->sku,
                'price' => $variation->price>0 ? $variation->price : NULL,
                'brand' => $product->brand,
                'ship_code' => $product->shipping_code,
                'was_price' => $variation->was_price>0 ? $variation->was_price : NULL,
                'quantity' => $variation->qty>0 ? $variation->qty : NULL,
                'is_active' => $variation->status=='active'?'1':'0',
            ];
        });
        return $items->toArray();
    }

    private function insert_or_update_inventory($items)
    {
        if ($this->edit) {
            $this->inventoryService->delete($items);
            $this->inventoryService->insert($items);
        } else {
            $this->inventoryService->insert($items);
        }
    }
}
