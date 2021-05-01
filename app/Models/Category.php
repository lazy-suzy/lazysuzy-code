<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = "mapping_core";

    public static $base_site_url = "https://www.lazysuzy.com";

    protected $listing_base_url = "/products";

    public static function get_categories($dept = null, $brands = null)
    {
        $c_cat = [];
        $base_site = request()->getHttpHost();
        $listing_base_url = "/products";

        if (isset($_GET['dept'])) {
            $dept = strtolower(trim($_GET['dept']));
        }

        $rows = Category::select(['cat_name_long', 'cat_name_url',
            'cat_image','LS_ID', 'filter_label', 'cat_name_short', 'cat_icon']);
        
        if ($dept != "all")
            $rows = $rows->where('dept_name_short', $dept);
        
        $rows = $rows->whereRaw('LENGTH(cat_name_short) != 0 AND LENGTH(cat_sub_name) = 0')
            ->get()
            ->toArray();

        foreach ($rows as $row) {
            $sub_categories = SubCategory::get_sub_categories($dept, $row['cat_name_url'], $brands);
            array_push($c_cat, [
                'category' => $row['cat_name_long'],
                'enabled' => !isset($brands),
                'product_category_' => $row['cat_name_url'],
                'filter_label' => ucfirst($row['filter_label']),
                'LS_ID' => $row['LS_ID'],
                'image' => Category::$base_site_url . '' . $row['cat_image'],
                'link' => $listing_base_url . '/' . strtolower($dept) . '/' . strtolower($row['cat_name_url']),
                'sub_categories' => $sub_categories
            ]);
        }
        return $c_cat;
    }

    public static function trending_categories($limit) {
        
        $trending_categories = [];
        $cols = [
            'dept_name_long', 'cat_name_long', 'cat_name_url',
            'cat_image', 'cat_name_short', 'dept_name_url'
        ];
        
        $rows = Category::select($cols)
            ->orderBy('rank', 'ASC')
            ->whereRaw('rank > 0')
            ->limit($limit)
            ->get()
            ->toArray();
        
            foreach($rows as $row) {
            array_push($trending_categories, [
                'category' => $row['cat_name_long'],
                'link' => '/products/' . $row['dept_name_url'] . "/" . $row['cat_name_url'],
                'image' => $row['cat_image']
            ]);
        }
        
        return $trending_categories;
        
    }

    /**
     * This function gets all the boaed categories, from mapping_core table 
     * there is a similar function in Departments.php that does something similar
     * but this function will not be as straighforward as that one.
     * 
     * If the board view is true then this will retund categories and sub-catgeories 
     * with board-view = 1 in the database, otherwise if 'is_board_view' is false
     * this will return only categories.
     *
     * @param boolean $get_board_categories -> get sub-categories for category also
     * @return array $catgeories - an associative array with "LSID" => category_obj
     */
    public static function get_board_categories($get_board_categories = true) {
        $cols = ["LS_ID", "dept_name_url", "cat_name_url", 
        "filter_label", "cat_image", "cat_sub_name", "cat_sub_url", "cat_icon"];
        $rows = Category::select($cols);

        if($get_board_categories) {
            $rows = $rows->where("board_view", 1);
        }
        else {
            $rows = $rows->whereRaw('LENGTH(cat_sub_name) = 0 AND LENGTH(cat_name_url) != 0'); 
        }

        $rows = $rows->get()->toArray();

        $categories = [];

        foreach ($rows as $row) {

            if(strlen($row['cat_sub_name']) == 0
                && strlen($row['cat_sub_url'] == 0) && $get_board_categories) {
                    // this is a valid category with board_view = 1 
                    // so inlcude all it's sub categories 
                    // in the output as well!
                    
                    $sub_cat_rows = Category::select($cols)
                        ->where('cat_name_url', $row['cat_name_url'])
                        ->whereRaw("LENGTH(cat_sub_name) > 0")
                        ->get()->toArray();
                    
                    foreach ($sub_cat_rows as $_row) {
                        $categories[$_row['LS_ID']] = [
                            'name' => strlen($_row['filter_label']) > 1 ? $_row['filter_label'] : $_row['cat_sub_name'],
                            'value' => $_row['LS_ID'],
                            'checked' => false,
                            'enabled' => false
                        ];
                    }
                }

            $categories[$row['LS_ID']] = [
                'name' => strlen($row['filter_label']) > 1 ? $row['filter_label'] : $row['cat_name_url'],
                'value' => $row['LS_ID'],
                'checked' => false,
                'enabled' => false
            ];
        }

        return $categories;
    }
}
