<?php

namespace App\Models;

use App\Models\Utility;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Brands extends Model
{
    protected $table = "master_brands";
    public static $base_site_url = "https://www.lazysuzy.com";
    public static $brand_mapping = [
        'pier1' => 'pier1_products',
        'cb2' => 'cb2_products_new_new',
        'cnb' => 'crateandbarrel_products',
        'westelm' => 'westelm_products_parents',
        'nw' => 'nw_products_API',
        'cab' => 'crateandbarrel_products',
    ];
    public static $product_id_brands = ["floyd", "westelm", "potterybarn"];
    public static $xbg_sites = ['nw', 'westelm'];


    public static function get_all($key)
    {
        $rows = Brands::select("*");

        if ($key !== null) $rows = $rows->where("value", $key);

        $rows = $rows->where('is_active', 1)->get()
            ->toArray();;

        $brands = [];

        foreach ($rows as $row) {
            array_push($brands, [
                'name' => $row['name'],
                'value' => $row['value'],
                'logo' => Brands::$base_site_url . $row['logo'],
                'url' => $row['url'],
                'description' => $row['description'],
                'cover_image' => Brands::$base_site_url . $row['cover_image'],
                'location' => $row['location'],
                'feature' => $row['feature']
            ]);
        }

        return $brands;
    }

    public static function get_banners()
    {

        $banners = [];
        $rows = DB::table("site_banners")
            ->select("*")
            ->where("is_active", 1)
            ->get();

        $agent = $_SERVER['HTTP_USER_AGENT'];

        if (Utility::is_mobile($agent)) {
            $image_col = "image_mobile";
            $font_col = "font_hex_mobile";
        } else {
            $image_col = "image";
            $font_col = "font_hex";
        }

        foreach ($rows as $row) {

            array_push($banners, [
                "name" => $row->name,
                "image" => Brands::$base_site_url . $row->$image_col,
                "link" => Brands::$base_site_url . $row->url,
                "link_label" => $row->link,
                "banner_label" => $row->label,
                "font_hex" => $row->$font_col,
                "position" => $row->position,
                "rank" => $row->rank
            ]);
        }

        return $banners;
    }

    public static function convert_normal_master_format($product, $min_price, $max_price, $pop_index, $dim = null)
    {

        $arr =  array(
            'product_sku'         => $product->product_sku,
            'sku_hash'            => $product->product_sku,
            'model_code'          => $product->model_code,
            'product_url'         => $product->product_url,
            'model_name'          => $product->model_name,
            'images'              => $product->images,
            'thumb'               => $product->thumb,
            'product_dimension'   => $product->product_dimension,
            'color'               => $product->color,
            'price'               => $product->price !== null ? $product->price : $product->was_price,
            'min_price'           => $min_price,
            'max_price'           => $max_price,
            'was_price'           => strlen($product->was_price) > 0 ? $product->was_price : $product->price,
            'product_name'        => $product->product_name,
            'product_feature'     => $product->product_feature,
            'collection'          => $product->collection,
            'product_set'         => $product->product_set,
            'product_condition'   => $product->product_condition,
            'product_description' => $product->product_description,
            'created_date'        => $product->created_date,
            'updated_date'        => $product->updated_date,
            'product_images'      => $product->product_images,
            'main_product_images' => $product->main_product_images,
            'site_name'           => $product->site_name,
            'brand'               => $product->brand,
            'reviews'             => $product->reviews,
            'rating'              => $product->rating,
            'master_id'           => $product->master_id,
            'LS_ID'               => $product->LS_ID,
            'popularity'          => $pop_index,
            'rec_order'           => $pop_index
        );


        if (in_array($product->site_name, Brands::$xbg_sites)) {
            $arr['image_xbg'] = $product->image_xbg;
        }

        if (isset($dims)) {
            $arr['dim_width'] = $dim['width'];
            $arr['dim_height'] = $dim['height'];
            $arr['dim_depth'] = $dim['depth'];
            $arr['dim_length'] = $dim['length'];
            $arr['dim_diameter'] = $dim['diameter'];
            $arr['dim_square'] = $dim['square'];
        }

        return $arr;
    }
    public static function convert_id_brand_master_data($product, $min_price, $max_price, $pop_index, $dim = null)
    {

        $arr =  array(
            'product_sku'         => $product->product_id,
            'sku_hash'            => $product->product_id_hash,
            'model_code'          => null,
            'product_url'         => $product->product_url,
            'model_name'          => null,
            'images'              => $product->product_images_path,
            'thumb'               => $product->thumb_path,
            'product_dimension'   => $product->product_dimension,
            'color'               => $product->color,
            'price'               => $product->price,
            'min_price'           => $min_price,
            'max_price'           => $max_price,
            'was_price'           => strlen($product->was_price) > 0 ? $product->was_price : $product->price,
            'product_name'        => $product->product_name,
            'product_feature'     => $product->description_details,
            'collection'          => $product->collection,
            'product_set'         => null,
            'product_condition'   => $product->description_shipping,
            'product_description' => $product->description_overview,
            'created_date'        => $product->created_date,
            'updated_date'        => $product->updated_date,
            'product_images'      => $product->product_images_path,
            'main_product_images' => $product->main_image_path,
            'site_name'           => $product->site_name,
            'brand'               => $product->brand,
            'reviews'             => 0,
            'rating'              => 0,
            'master_id'           => null,
            'LS_ID'               => $product->LS_ID,
        );

        if ($product->site_name !== 'westelm') {
            $arr['popularity'] = $pop_index;
            $arr['rec_order']  = $pop_index;
        }


        if (in_array($product->site_name, Brands::$xbg_sites)) {
            $arr['image_xbg'] = $product->image_xbg;
        }

        if (isset($dims)) {
            $arr['dim_width'] = $dim['width'];
            $arr['dim_height'] = $dim['height'];
            $arr['dim_depth'] = $dim['depth'];
            $arr['dim_length'] = $dim['length'];
            $arr['dim_diameter'] = $dim['diameter'];
            $arr['dim_square'] = $dim['square'];
        }

        return $arr;
    }

    public static function get_brand_LS_IDs($brands)
    {
        $brand_LSID_rows = DB::table(config('tables.master_table'))
            ->select('LS_ID')
            ->whereIn('brand', $brands)
            ->where('product_status', 'active')
            ->groupBy('LS_ID')
            ->get();

        $brand_LSID_array = [];
        foreach ($brand_LSID_rows as $row) {
            $array = explode(",", $row->LS_ID);
            foreach ($array as $a)
                if (strlen($a) > 0)
                    $brand_LSID_array[$a] = true;
        }

        return $brand_LSID_array;
    }
}
