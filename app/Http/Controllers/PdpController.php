<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentMapping;
use App\Models\Product;
use App\Models\Utility;
use App\Services\ProductService;
use Illuminate\Http\Request;

class PdpController extends Controller
{
    public function index($sku, Request $request)
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        
        if (Utility::is_mobile($agent)) {
            return view('pages.detailspage');
        }
        else {
            $product_LS_ID = Product::get_product_LS_ID($sku);
            if ($product_LS_ID != null) {
                $depts = Department::get_department_info($product_LS_ID);
                if (sizeof($depts) > 0) {
                    $redirect_url = $depts[0]['category_url'] . "?model_sku=" . $sku;
                    return redirect($redirect_url);
                } else {
                    // PRODUCT has no departments/category. Not possible tho.
                }
            }
            else {
                // redirect to NO PRODUCT FOUND PAGE.
                return abort(404);
            }
        }
        
    }

}
