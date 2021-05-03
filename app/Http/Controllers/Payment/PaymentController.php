<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payments\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller 
{
    public static function charge_client(Request $req) {

        return Payment::charge($req);
    }

    public static function get_order(Request $req) {

        $order_id = $req->input('order_id');
        if(!isset($order_id) || strlen($order_id) == 0) {
            return [
                'msg' => 'Invalid order ID'
            ];
        }

        return Payment::order($order_id);
    }
}