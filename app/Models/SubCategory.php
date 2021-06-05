<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SubCategory extends Model
{
    protected $table = "mapping_core";

    public static function get_sub_categories($department, $category, $brands = null)
    {
        $sub_categories = [];
        $rows = SubCategory::select(['cat_sub_name', 'LS_ID', 'cat_sub_url']);

        if ($department != "all")
            $rows = $rows->where('dept_name_short', $department);
        
        $rows = $rows->where('cat_name_url', $category)
            ->whereRaw('LENGTH(cat_sub_name) != 0')
            ->get()
            ->toArray();

        foreach ($rows as $row) {
            array_push($sub_categories, [
                'sub_category' => $row['cat_sub_name'],
                'enabled' => !isset($brands),
                'link' => "/products/" . strtolower($department) . "/" . strtolower($category) . "/" . strtolower( $row['cat_sub_url']), 
                'LS_ID' => $row['LS_ID']
            ]);
        }
        return $sub_categories;
    }

    /**
     * This will take catgeory LS_ID and output an array of 
     * sub-category LS_IDs included in the catgeory
     *
     * @param [type] $cat_LS_ID
     * @return void
     */
    public static function get_sub_cat_LSIDs($cat_LS_IDs) 
    {
        $LS_IDs = [];
        $categories = []; // LS_ID
        $sub_categories = []; // LS_ID
        
        // we will only find sub-cat for catgeory LSIDs, 
        // NOTE: there are some LS_IDs shown on the board that are 
        // sub-catgeories them selves. So using those to find sub-catgories 
        // will produce additional LS_IDs
        // if the LS_ID is already a sub-cat then just include it and don't 
        // do anthing, if LS_ID is category LSID then find it's sub-cat LS_IDs
        $rows = SubCategory::whereIn('LS_ID', $cat_LS_IDs)->get();

        // we'll have to get the cat_name_url value because then only we can
        // search for sub-categories
        // SELECT * FROM mapping_core WHERE cat_name_long = (SELECT cat_name_url FROM mapping_core WHERE LS_ID = '210');

        // checking for wrong input, because we're going ot use 
        // this input directly in the query, there is some scope of 
        // security flaws
        for ($i = 0; $i < sizeof($cat_LS_IDs); $i++) {
            /*if(!is_numeric($cat_LS_IDs[$i])) return null;
            else*/ 
            $cat_LS_IDs[$i] = '\'' . $cat_LS_IDs[$i] . '\'';
        }

        foreach($rows as $row) {
            if(strlen($row->cat_sub_url) == 0) {
                // this is a category LS_ID
                $categories[] = $row->LS_ID;
            }
            else {
                // this is a sub-catgeory LS_ID
                $sub_categories[] = $row->LS_ID;
            }
        }
        //$LS_ID_string = implode(",", $categories);
        $LS_ID_string = "'" . implode ( "', '", $categories ) . "'"; 
        $LS_ID_string  = '(' . $LS_ID_string . ')';

        if(strlen($LS_ID_string) != 2) {
            $rows = DB::select(DB::raw("SELECT LS_ID FROM mapping_core WHERE cat_name_long in 
                (SELECT cat_name_long FROM mapping_core WHERE LS_ID in $LS_ID_string)
                AND dept_name_long IN (SELECT dept_name_long FROM mapping_core WHERE LS_ID IN $LS_ID_string)"));

            foreach ($rows as $r) {
                $LS_IDs[] = $r->LS_ID;
            }
        }
       

        return array_merge($LS_IDs, $sub_categories);
    }
}
