<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Search;

class SearchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
    }

    public function get_all() {
        $search_data = Search::get_all();
        return sizeof($search_data) > 0 
            ? response()->json($search_data, 200) 
            : response()->noContent(); // status code 204
    }
}
