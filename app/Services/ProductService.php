<?php
/**
 * Created by PhpStorm.
 * User: kallolpratimsaikia
 * Date: 2019-03-14
 * Time: 12:33
 */

namespace App\Services;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Warehouse\Services\BaseService;

class ProductService extends BaseService
{
    public function addToWishList(Request $request)
    {
        $user = Auth::user();
        $user->products()->attach($request->product_id);
        return $user;
    }

}