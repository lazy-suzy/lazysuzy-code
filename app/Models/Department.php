<?php

namespace App\Models;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Department extends Model
{
    protected $table = "mapping_core";
    public static $base_siteurl = "https://www.lazysuzy.com";
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'dept_name_urlcategories');
    }

    public static function get_all_departments($dept_name_url_api = true, $is_home_call = false,
     $is_board_view = false, $brands = null)
    {
        $departments = [];
        $base_site   = request()->getHttpHost();
        
        
        $rows        = Department::select(['dept_name_long', 'dept_name_url', 'LS_ID', 'dept_name_short', 'dept_image', 'board_view'])
            ->whereRaw('LENGTH(cat_name_short) = 0 AND LENGTH(cat_sub_name) = 0')
            ->get()
            ->toArray();

        $departments['all_departments'] = [];

        // if brands have value only send data that is applicable to those brands
        if(isset($brands)) {
           $brand_LSID_array = Brands::get_brand_LS_IDs($brands);          
        }

        foreach ($rows as $row) {
            $dept_LS_ID = $row['LS_ID'];

            $categories = null;
            if (!$is_home_call) {
                $dept       = $row['dept_name_long'];
                $categories = Category::get_categories($row['dept_name_short'], $brands);

                array_push($departments['all_departments'], [
                    'department' => $dept,
                    'enabled'    => !isset($brands), // if brands filter is present disable all department and only enable for brands
                    'LS_ID'      => $dept_LS_ID,
                    'link'       => '/products/' . strtolower($row['dept_name_url']),
                    'categories' => $categories
                ]);
            }
            else {
                $dept       = $row['dept_name_long'];
                array_push($departments['all_departments'], [
                    'department' => $dept,
                    'LS_ID'      => $dept_LS_ID,
                    'link'       => '/products/' . strtolower($row['dept_name_url']),
                    'image'      => Department::$base_siteurl . $row['dept_image'],
                    'categories' => $categories,
                ]);
            }
        }


        if(isset($brands)) {
            foreach ($departments['all_departments'] as &$data) {
                if (isset($brand_LSID_array[$data['LS_ID']])) {
                    $data['enabled'] = true;
                }

                foreach ($data['categories'] as &$cat) {
                    if (isset($brand_LSID_array[$cat['LS_ID']])) {
                        $cat['enabled'] = true;

                        // move enabled = true to department
                        $data['enabled'] = true;
                    }

                    foreach ($cat['sub_categories'] as &$sub_cat) {
                        if (isset($brand_LSID_array[$sub_cat['LS_ID']])) {
                            $sub_cat['enabled'] = true;
                            $cat['enabled'] = true;
                            $data['enabled'] = true;
                        }
                    }
                }
            }
        }
        
        if ($dept_name_url_api && !$is_home_call) {
            // trending categories will return N top results. Pass N as argument.
            $departments['trending_categories'] = Category::trending_categories(5);
            $departments['trending_products'] = Product::trending_products(10);
        }
        return $departments;
    }

    public static function get_department_info($LS_ID)
    {

        $LS_IDs = explode(",", $LS_ID);
        $rows = Department::select("*")
            ->whereIn('LS_ID', $LS_IDs)
            ->get()
            ->toArray();

        $dept_info = [];
        foreach ($rows as $key => $value) {
            array_push($dept_info, [
                'department_name' => $value['dept_name_long'],
                'department_url' => '/products/' . $value['dept_name_url'],
                'category_name' => $value['cat_name_short'],
                'category_url' => '/products/' . $value['dept_name_url'] . '/' . $value['cat_name_url'],
                'sub_category_name' => $value['cat_sub_name'],
                'sub_category_url' => '/products/' . $value['dept_name_url'] . '/' . $value['cat_name_url'] . '/' . $value['cat_sub_url'],
            ]);
        }
        return $dept_info;
    }

    public static function get_single_department($dept)
    {

        $c_cat = [];
        $dept  = strtolower(trim($dept));
        $dept_long = $dept;
        $dept_image = NULL;
        $row   = Department::select(['dept_name_short', 'LS_ID', 'dept_name_long', 'dept_image']);

        if ($dept != "all")
            $row = $row->where('dept_name_url', $dept);
        
            $row = $row->whereRaw('LENGTH(cat_name_long) = 0 AND LENGTH(cat_sub_name) = 0')
            ->get()
            ->toArray();
        if (isset($row[0]['LS_ID'])) {
            $dept       = $row[0]['dept_name_short'];
            $dept_long  = $row[0]['dept_name_long'];
            $dept_image = $row[0]['dept_image']; 
            $dept_LS_ID = $row[0]['LS_ID'];
        } else {
            return null;
        }
        
        $categories = Category::get_categories($dept);
        foreach ($categories as $category) {
            $sub_categories = SubCategory::get_sub_categories($dept, $category['category']);
            array_push($c_cat, [
                'category'       => $category['category'],
                'LS_ID'          => $category['LS_ID'],
                'image'          => $category['image'],
                'link'          => $category['link'],
                'sub_categories' => $sub_categories,
            ]);
        }
    
        return [
            'department' => $dept,
            'department_long' => $dept_long,
            'department_image' => env('APP_URL') . $dept_image,
            'LS_ID'      => $dept_LS_ID,
            'categories'  => $c_cat,
        ];
    }


    public static function get_board_categories()
    {
        $cols = ["LS_ID", "dept_name_url", "cat_name_url", "filter_label", "cat_image", "cat_icon"];
        $rows = Department::select($cols)
            ->where("board_view", 1)
            ->get()
            ->toArray();

        $categories = [];
        foreach ($rows as $row) {
            array_push($categories, [
                'category' => $row['filter_label'],
                'LS_ID' => $row['LS_ID'],
                'link' => '/products/' . $row['dept_name_url'] . '/' . $row['cat_name_url'],
                'image' => env('APP_URL') . $row['cat_image'],
                'icon' => $row['cat_icon']
            ]);
        }

        return $categories;
    }
}
