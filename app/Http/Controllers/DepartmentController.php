<?php

namespace App\Http\Controllers;

use App\Models\Brands;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;


class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $is_board_view = filter_var(Input::get('board-view'), FILTER_VALIDATE_BOOLEAN);
        $is_home_call = filter_var(Input::get('home'), FILTER_VALIDATE_BOOLEAN);
        $brands = Input::get('brands');
        $brands = isset($brands) && strlen($brands) > 0 ? explode(",", $brands) : null;


        if ($is_board_view) 
            return response()->json(Department::get_board_categories());

        // 1st parameter is for attaching trending products and trending categories
        return response()->json(Department::get_all_Departments(true, $is_home_call, $is_board_view, $brands));
    }

    public function get_department($dept)
    {

        return response()->json(Department::get_single_department($dept));
    }
}
