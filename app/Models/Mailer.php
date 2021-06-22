<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models;
use App\Models\Payments\Payment;

class Mailer extends Mailable
{

    use Queueable, SerializesModels;
    private static $order_status_table = 'lz_orders';

    public static function get_mailer_products($user_mail = null)
    {

        // get_mailer_products will get products from the database
        // for user where E-Mail = $user_mail

        if ($user_mail != null) {
            // real logic goes here
        } else {
            // right now mailer is in testing phase
            // so sending on-sale products of LS_ID = 202 as per @Arzan's directions

            $LS_IDs = ["202"];
            $limit = 10;

            $products = DB::table('master_data')
                ->whereRaw('price >  0')
                ->whereRaw('was_price > 0')
                ->whereRaw('LS_ID REGEXP "' . implode("|", $LS_IDs) . '"')
                ->orderBy(DB::raw("`price` / `was_price`"), 'asc')
                ->join("master_brands", "master_data.site_name", "=", "master_brands.value")
                ->limit($limit)
                ->get();

            $mailer_products = [];
            foreach ($products as $product) {
                $product_details = Product::get_details($product, null, true, false, false, false);
                // make lazysuzy native product URL
                $product_details['local_url'] = env('APP_URL') . '/product/' . $product_details['sku'];
                $mailer_products[] = $product_details;
            }

            //return $mailer_products;

            /*
            ====================== MAILER PRODUCTS OBJECT STRUCTURE ===========================
            {
                id=> 2,
                sku=> "PS71108",
                is_new=> false,
                site=> "Pier 1",
                name=> "Luis Upholstered Build Your Own Outdoor Sectional",
                product_url=> "https=>//www.pier1.com/luis-upholstered-build-your-own-outdoor-sectional/PS71108.html",
                product_detail_url=> "https=>//www.lazysuzy.com/product/PS71108",
                is_price=> "49.98-239.98",
                was_price=> "499.95-799.95",
                percent_discount=> "90.00",
                model_code=> "",
                color=> "",
                collection=> "",
                condition=> "Clearance,Indoor/Outdoor",
                main_image=> "https=>//www.lazysuzy.com/Pier-1/pier1_images/PS71108_main.jpg",
                reviews=> 1,
                rating=> 3,
                wishlisted=> false,
                local_url=> "https=>//www.lazysuzy.com/product/PS71108"
            }
            */

            echo json_encode($mailer_products);
            //die();
            return $mailer_products;
        }
    }

    public function build()
    {
        $email = "aditya@lazysuzy.com";
        $subject = "Welcome to LazySuzy!";
        $name = "Aditya Saxena";

        return $this->view('pages.productmailer')
            ->from($email, $name)
            ->cc($email, $name)
            //->replyTo('arzan@lazysuzy.com', $name)
            ->subject($subject)
            ->with([
                'products' => $this->get_mailer_products()
            ]);
    }


    public static function send_receipt($to, $to_name, $mail_data, $mail_template_id = null)
    {

        $mail_template = isset($mail_template_id) ? $mail_template_id : env('MAILER_RECEIPT_TEMPLATE_ID');

        $curl = curl_init();
        $subject = str_replace("$", " ", env('MAILER_RECEIPT_SUBJECT'));
        $dy_data = [
            "personalizations" => [
                [
                    "to" => [
                        [
                            "email" => $to,
                            "name" => $to_name
                        ]
                    ],
                    "dynamic_template_data" => $mail_data,
                    "subject" => $to_name . ", " . $subject
                ]
            ],
            "from" => [
                "email" => env('MAILER_FROM'),
                "name" => env('MAILER_FROM_NAME')
            ],
            "reply_to" => [
                "email" => env('MAILER_FROM'),
                "name" => env('MAILER_FROM_NAME')
            ],
            "template_id" => $mail_template
        ];

        if (isset($mail_template_id)) {
            $dy_data['personalizations'][0]['bcc'] = [
                [
                    'email' => 'www.lazysuzy.com+d82844d1fe@invite.trustpilot.com'
                ],
                [
                    'email' => 'hello@lazysuzy.com'
                ]
            ];
        }

        $auth = [
            "authorization: Bearer " . env('MAILER_STRIPE_KEY'),
            "content-type: application/json"
        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => env('MAILER_SG_API'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($dy_data),
            CURLOPT_HTTPHEADER => $auth
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpcode != 202 || $err) {
            return [
                'status' => false,
                'error' => isset($response->errors) ? $response->errors : $err,
                'response' => $response
            ];
        }

        return [
            'status' => true
        ];
    }

    public static function trigger_mail($data) {
        $mailer_type = isset($data['type']) ? $data['type'] : null;
        if(is_null($mailer_type)) return [
            'error' => 'invalid mailer type'
        ];

        switch($mailer_type) {
            case '9': 
                return self::order_delivered_mail($data);
            break;
            default:
                return [
                    'error' => 'invalid mailer type'
                ];
            break;
        }
    }

    private static function order_delivered_mail($data) {
        $order_id = isset($data['order_id']) ? $data['order_id'] : null;
        $product_skus = isset($data['skus']) ? explode(",", $data['skus']) : null;

        if(is_null($order_id) || is_null($product_skus)) {
            return ['error' => 'Invalid order_id or product skus data recieved.'];
        }

        // mark product_skus as Delivered in DB and then trigger mail for the same.
        $updated_rows = DB::table(self::$order_status_table)
            ->where('order_id', $order_id)
            ->whereIn('product_sku', $product_skus)
            ->update(['status' => 'Delivered']);

            $order_details = Payment::order($order_id);
            $mailer_data = [];
            $delivered_products = [];
    
            $rows = DB::table(self::$order_status_table)->select(['product_sku'])
                ->where('order_id', $order_id)
                ->where('status', 'Delivered')
                ->where('email_notification_sent', 0)
                ->get()
                ->toArray();

            $rows = array_column($rows, "product_sku");
            foreach ($order_details['cart']['products'] as $product) {
                if (in_array($product['product_sku'], $rows)
                    && in_array($product['product_sku'], $product_skus)) {
                    $delivered_products[] = $product;
                }
            }

            $send  = [
                'error' => null,
                'message' => 'No prducts from to trigger the mail.'
            ];
    
            if (sizeof($delivered_products) > 0) {
                $mailer_data['products'] = $delivered_products;
    
                $name_and_company = "";
                $shipping_addr = "";
    
                if(strlen($order_details['delivery'][0]->shipping_f_Name) > 0)
                    $name_and_company  = $order_details['delivery'][0]->shipping_f_Name;
    
                if(strlen($order_details['delivery'][0]->shipping_l_Name) > 0)
                    $name_and_company .= " " . $order_details['delivery'][0]->shipping_l_Name;
    
                if(strlen($order_details['delivery'][0]->shipping_company_name))
                    $name_and_company .= ", " . $order_details['delivery'][0]->shipping_company_name;
    
                if(strlen($order_details['delivery'][0]->shipping_address_line1) > 0)
                    $shipping_addr = $order_details['delivery'][0]->shipping_address_line1;
                
                if(strlen($order_details['delivery'][0]->shipping_address_line2) > 0) {
                    $shipping_addr .= ", " . $order_details['delivery'][0]->shipping_address_line2;
                }
    
                if(strlen($order_details['delivery'][0]->shipping_city) > 0) {
                    $shipping_addr .= ", " . $order_details['delivery'][0]->shipping_city;
                }
    
                if(strlen($order_details['delivery'][0]->shipping_state) > 0) {
                    $shipping_addr .= ", " . $order_details['delivery'][0]->shipping_state;
                }
    
                if(strlen($order_details['delivery'][0]->shipping_country) > 0) {
                    $shipping_addr .= ", " . $order_details['delivery'][0]->shipping_country;
                }
                
                if(strlen($order_details['delivery'][0]->shipping_zipcode) > 0) {
                    $shipping_addr .= ", " . $order_details['delivery'][0]->shipping_zipcode;
                }
                
    
                $mailer_data['shipping_f_name'] = $order_details['delivery'][0]->shipping_f_Name;
                $mailer_data['shipping_l_name'] = $order_details['delivery'][0]->shipping_l_Name;
                $mailer_data['shipping_addr_line_1'] = $order_details['delivery'][0]->shipping_address_line1;
                $mailer_data['shipping_addr_line_2'] = $order_details['delivery'][0]->shipping_address_line2;
                $mailer_data['shipping_state'] = $order_details['delivery'][0]->shipping_state;
                $mailer_data['shipping_city'] = $order_details['delivery'][0]->shipping_city;
    
                $mailer_data['shipping_contry'] = $order_details['delivery'][0]->shipping_country;
                $mailer_data['shipping_zipcode'] = $order_details['delivery'][0]->shipping_zipcode;
                $mailer_data['shipping_company'] = $order_details['delivery'][0]->shipping_company_name;
                $mailer_data['order_id'] = $order_details['delivery'][0]->order_id;
                $mailer_data['email'] = $order_details['delivery'][0]->email;
                $mailer_data['name'] =  $mailer_data['shipping_f_name'] . " " . $mailer_data['shipping_l_name'];
                $mailer_data['name_and_company'] = $name_and_company;
                $mailer_data['shipping_addr'] = $shipping_addr;
    
                $send = Mailer::send_receipt($mailer_data['email'], $mailer_data['name'], $mailer_data, env('MAILER_RECEIPT_ORDER_DELIVERED_TEMPLATE_ID'));
                if ($send['status']) {
                    foreach ($delivered_products as $product) {
                        $sku = $product['product_sku'];
                        DB::table(self::$order_status_table)
                            ->where('order_id', $order_id)
                            ->where('product_sku', $sku)
                            ->update([
                                'email_notification_sent' => DB::raw('CONCAT(email_notification_sent, 9)')
                            ]);
                    }
                }
            }
        
        return $send;
        
    }
}
