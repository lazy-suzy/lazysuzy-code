<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::all();
        return $categories;
    }

    public function get_all_categories()
    {
        return response()->json(Category::get_categories());
    }
   

}
