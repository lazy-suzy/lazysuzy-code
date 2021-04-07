<?php

namespace App\Http\Controllers;


use App\Models\Variations;
use App\Models\SellerProduct;
use App\Models\SellerBrands;
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
        return SellerProduct::save_sellerProduct($data);
    }
	
	public function get_masterdatascript()
    {
        return Variations::get_masterdatascript();
    }
	
	public function save_sellerBrands(Request $request)
    {
		$data = $request->all();
        return SellerProduct::save_sellerbrand($data);
    }
	
	public function get_sellerBrands()
    {
        return SellerProduct::get_all();
    }
}
