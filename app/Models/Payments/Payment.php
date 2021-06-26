<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use App\Models\Inventory;
use Illuminate\Support\Str;

use App\Models\Mailer;
use App\Models\Utility;
use Stripe;
use Auth;
use Exception;
use Illuminate\Support\Facades\Config;
use App\Models\PromoDiscount;

class Payment extends Model
{
    private static $order_table = 'lz_orders';
    private static $delivery_table = 'lz_order_delivery';
    private static $shipment_code_table = 'lz_ship_code';
    private static $failed_reciepts = 'lz_failed_receipt';

    public static function charge($req)
    {
        // there will always be a user auth 
        $user = Auth::user();
        $user_id = $user->id;
        $username = null;

        if (strlen($user->first_name) > 1) $username = $user->first_name;
        else $username = $req->input('billing_f_Name');

        $mail_data = [];
        $total_price = 0;
        $total_items = 0;
        $shipment_cost = 0;
        $mail_data['order'] = [];
        $mail_data['order']['products'] = [];

        $mail_data['username'] = $username;

        $shipping_state = $req->input('shipping_state');
        $promo_code = $req->input('promo');

        $cart = Cart::cart($shipping_state, $promo_code);

        // generte and random string of length 5
        // handle edge case, if there are more than 100 collisions then shift to length +1 
        $length = 5;
        $collisions = 0;
        $order_id = strtoupper(Str::random($length));
        $found = DB::table(Payment::$order_table)
            ->where('order_id', $order_id)
            ->count();

        while ($found) {
            $order_id = strtoupper(Str::random($length));
            $found = DB::table(Payment::$order_table)
                ->where('order_id', $order_id)
                ->count();
            $collisions++;

            if ($collisions > 100) {
                $length += 1;
                $collisions = 0;
            }
        }

        //$order_id = "lz-ord-" . rand(1, 1000) . "-" . rand(1, 10000);
        $deal_SKUs = [];
        foreach ($cart['products'] as $product) {

            // add product SKUs that have a code linking them to any kind of deal.
            if (isset($product->product_origin) && $product->product_origin != NULL) {
                $deal_SKUs[$product->product_origin][] = [
                    "sku" => $product->product_sku,
                    "count" => $product->count
                ];
            }

            // add mail reciept data
            $mail_data['order']['products'][] = [
                'name' => $product->product_name,
                'price' => '$' . $product->retail_price,
                'count' => $product->count,
                'image' => $product->image,
                'brand' => $product->site,
                'sku' => $product->product_sku,
                'showCount' => $product->count > 1 ? true : false

            ];

            $row = DB::table('lz_inventory')
                ->select('quantity')
                ->where('product_sku', $product->product_sku)
                ->get();

            if (isset($row[0])) {
                $available_count = $row[0]->quantity;
                if ($available_count < $product->count) {
                    return [
                        'status' => 'failed',
                        'msg' => 'Product ' . $product->product_name . ' not available.'
                    ];
                }
				
                DB::table('lz_orders')
                    ->insert([
                        'user_id' => $user_id,
                        'order_id' => $order_id,
                        'product_sku' => $product->product_sku,
                        'price' => (float) $product->retail_price,
                        'quantity' => $product->count
                    ]);
            
			// ****************************
			
			
				
			
			
       
			
			
			} else {
                return [
                    'status' => 'failed',
                    'msg' => 'Product ' . $product->name . ' not in inventory.'
                ];
            }
        }

        $total_price = round($cart['order']['total_cost'], 2);
        $sub_total = $cart['order']['sub_total'];
        $shipment_cost = $cart['order']['shipment_total'];
        $sales_tax = $cart['order']['sales_tax_total'];

        $mail_data['total_price'] = '$' . $sub_total;

        // add sales tax cost to total
        $sales_tax = $cart['order']['sales_tax_total'];

        $mail_data['shipping'] = '$' . $shipment_cost;
        $mail_data['order_cost'] = '$' . $total_price;
        $mail_data['sales_tax'] = '$' . $sales_tax;


        $errors = [];
        try {

            // insert record for transaction
            DB::table('lz_transactions')
                ->insert([
                    'user_id' => $user_id,
                    'stripe_customer_id' => 'ONGOING',
                    'stripe_transaction_id' => "ONGOING",
                    'order_id' => $order_id,
                    'checkout_amount' => $total_price,
                    'status' => 'ONGOING',
                ]);

            Stripe\Stripe::setApiKey(env('STRIP_SECRET'));

            $customer = Stripe\Customer::create([
                'name' => $req->input('billing_f_Name') . ' ' . $req->input('billing_l_Name'),
                'address' => [
                    'line1' => $req->input('billing_address_line1'),
                    'city' => $req->input('billing_city'),
                    'state' => $req->input('billing_state'),
                    'country' => substr($req->input('billing_country'), 0, 2),
                    'postal_code' => $req->input('billing_zipcode'),
                ],
                'email' => $req->input('email'),
                'phone' => $req->input('shipping_phone'),
                "source" => $req->input('token')
            ]);

            DB::table('lz_transactions')
                ->where('order_id', $order_id)
                ->update([
                    'stripe_customer_id' => $customer->id
                ]);
            // add dump for orders API 
            DB::table('lz_order_dump')
                ->insert([
                    'user_id' => $user_id,
                    'order_id' => $order_id,
                    'order_json' => json_encode($cart)
                ]);

            $charge = Stripe\Charge::create([
                'customer' => $customer->id,
                "amount" => $total_price * 100,
                "currency" => "usd",
                "description" => "Payment from Lazysuzy.com",
                'shipping' => [
                    'address' => [
                        'line1' => $req->input('shipping_address_line1'),
                        'city' => $req->input('shipping_city'),
                        'state' => $req->input('shipping_state'),
                        'country' => substr($req->input('billing_country'), 0, 2),
                        'postal_code' => $req->input('shipping_zipcode'),
                    ],
                    'name' => $req->input('shipping_f_Name') . ' ' . $req->input('shipping_l_Name'),
                    'phone' => $req->input('shipping_phone')
                ]

            ]);

            DB::table('lz_transactions')
                ->where('order_id', $order_id)
                ->update([
                    'stripe_transaction_id' => $charge->id,
                    'status' => $charge->status
                ]);

            if ($charge->status == 'succeeded') {

                self::update_deal_stock($deal_SKUs);

                // remove from stock;
                foreach ($cart['products'] as $product) {
                    $p = $product;
                    $in_stock = DB::table('lz_inventory')
                        ->select("quantity")
                        ->where("product_sku", $p->product_sku)
                        ->get();

                    if (isset($in_stock[0])) {
                        $original_count = $in_stock[0]->quantity;
                        $left_count = $original_count - $p->count;

                        Cart::remove($p->product_sku, $p->count);

                        DB::table('lz_inventory')
                            ->where('product_sku', $p->product_sku)
                            ->update([
                                'quantity' => $left_count
                            ]);
                    }
                }

                $delivery_details = $req->all();
                $delivery_details['order_id'] = $order_id;
                $delivery_details['user_id'] = $user_id;
                $ID = DB::table('lz_order_delivery')
                    ->insertGetId($delivery_details);
                $customer_name = $req->input('billing_f_Name');

                // get card details for sending in email reciept
                $card = Stripe\Token::retrieve($req->input('token'));
                $mail_data['card'] = [
                    'last4' => $card->card->last4,
                    'expiry' => $card->card->exp_month . '/' . $card->card->exp_year
                ];
                $mail_data['shipping_details'] = $delivery_details;
                $mail_data['order_id'] = $order_id;
                $receipt_send = Mailer::send_receipt($req->input('email'), $customer_name, $mail_data);

                if (!$receipt_send['status']) {
                    // save this order in DB to send the 
                    // mail later
                    DB::table(Payment::$failed_reciepts)
                        ->insert([
                            'email' => $req->input('email'),
                            'mail_data' => json_encode($mail_data),
                            'error' => $receipt_send['error']
                        ]);
                }
				
				if(isset($promo_code) && $promo_code!=''){
				 $promo = PromoDiscount::decreasePromoCount($cart, $promo_code);

			}


                return [
                    'status' => $charge->status,
                    'order_id' => $order_id,
                    'amount' => $total_price,
                    'transaction_id' => isset($charge->id) ? $charge->id : null,
                    'reciept_url' => isset($charge->receipt_url) ? $charge->receipt_url : null,
                    'order' => $req->all(),
                    'receipt_send' => $receipt_send,
                    'mail_data' => $mail_data,
                    'card' => $card
                ];
            }
        } catch (Stripe\Exception\CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
            $errors = [
                'status' => $e->getHttpStatus(),
                'type' => $e->getError()->type,
                'code' => $e->getError()->code,
                'message' => $e->getError()->message
            ];

            DB::table('lz_transactions')
                ->where('order_id', $order_id)
                ->update([
                    'status' => $errors['code'] . " | " . $errors['message']
                ]);

            return [
                'status' => 'failed',
                'errors' => $errors,
                'order_id' => $order_id,
            ];
        } catch (Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
            $errors = [
                'status' => $e->getHttpStatus(),
                'type' => $e->getError()->type,
                'code' => $e->getError()->code,
                'message' => 'Rate Limit Exceeded. You can not make a payment of this amount',
            ];

            DB::table('lz_transactions')
                ->where('order_id', $order_id)
                ->update([
                    'status' => $errors['code'] . " | " . $errors['message']
                ]);
            return [
                'status' => 'failed',
                'errors' => $errors,
                'order_id' => $order_id,
            ];
        } catch (Exception $e) {
            // Something else happened, completely unrelated to Stripe
            $errors = [
                'status' => 402,
                'message' => $e->getMessage(),
            ];

            DB::table('lz_transactions')
                ->where('order_id', $order_id)
                ->update([
                    'status' => $errors['status'] . " | " . $errors['message']
                ]);
            return [
                'status' => 'failed',
                'errors' => $errors,
                'order_id' => $order_id,
            ];
        }
    }

    private static function update_deal_stock($deal_skus)
    {
        foreach ($deal_skus as $deal_code => $skus) {
            foreach ($skus as $sku_details) {

                DB::table(Config::get('tables.blowout_deals'))
                    ->where('product_sku', $sku_details['sku'])
                    ->where('deal_code', $deal_code)
                    ->update(['purchased_quantity' => DB::raw('purchased_quantity + ' . $sku_details['count'])]);
            }
        }
    }

    public static function order($order_id)
    {
        $response = [];
        $order_ = DB::table('lz_order_dump')
            ->where('order_id', $order_id)
            ->get();

        if (!isset($order_[0])) return null;

        $order_ = $order_[0];
        $order_data = json_decode($order_->order_json, true);
      
        $response['cart'] = $order_data; // $order_data['products'];

        $response['delivery'] = DB::table(Payment::$delivery_table)
            ->where('order_id', $order_id)
            ->get();

        $response['payment'] = [];
        if (isset($response['delivery'][0])) {
            Stripe\Stripe::setApiKey(env('STRIP_SECRET'));
            $response['payment'] = Stripe\Token::retrieve($response['delivery'][0]->token);
        }

        return $response;
    }
}
