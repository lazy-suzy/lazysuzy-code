<?php

namespace App\Models;

use App\Models\Collections;
use App\Http\Controllers\ProductController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

use Auth;

class ProductCategory extends Model
{
  
    public static $base_siteurl = 'https://www.lazysuzy.com';

    public static function get_dept_list()
    {

        $arr = [];
        $rows = DB::table("mapping_core")
            ->select(['dept_name_long', 'dept_name_short', 'dept_name_url'])
			->where('dept_name_url','!=','')
			->distinct('dept_name_url') 
			->orderBy('dept_name_url','ASC')
            ->get();

        foreach ($rows as $product) {
            array_push($arr, $product);
        }

        return $arr;
    }
 
    public static function get_cat_list($deptname)
    {

        $arr = [];
        $rows = DB::table("mapping_core")
            ->select(['cat_name_long', 'cat_name_short', 'cat_name_url'])
			->distinct('cat_name_url') 
			->where('dept_name_url', $deptname)
			->where('cat_name_url','!=','')
			->orderBy('cat_name_url','ASC')
            ->get();

        foreach ($rows as $product) {
            array_push($arr, $product);
        }

        return $arr;
    }
	
	
	public static function get_subcat_list($catname,$deptname)

    {

        $arr = [];
        $rows = DB::table("mapping_core")
            ->select(['cat_sub_name', 'cat_sub_url'])
			->distinct('cat_sub_name') 
			->where('cat_name_url', $catname)
			->where('dept_name_url', $deptname)
			->where('cat_sub_url','!=','')
			->orderBy('cat_sub_url','ASC')
            ->get();

        foreach ($rows as $product) {
            array_push($arr, $product);
        }

        return $arr;
    }
	


};
