<?php

namespace App\Http\Controllers;


use App\Models\Variations;
use Illuminate\Http\Request;

use Auth;
use Illuminate\Support\Facades\Validator;
use Subscribe as GlobalSubscribe;


class SellerDBController extends Controller
{
    public function index()
    {
        //
    }
	
	public function get_variation_label()
    {
        return Variations::get_seller_variation_label();
    }	
	
	public function get_variation_value($varid)
    {
        return Variations::get_variation_value($varid);
    }
	
	public function save_sellerVariation(Request $request)
    {
		$data = $request->all();
        return Variations::save_sellerVariation($data);
    }
}
