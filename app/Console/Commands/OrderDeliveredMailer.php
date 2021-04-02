<?php

namespace App\Console\Commands;

use App\Models\Mailer;
use App\Models\Payments\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class OrderDeliveredMailer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'OrderDeliveredMailer:TriggerMail {orderID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger order delivered mail when order status changes to divilered.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // fetch data related to order
        $order_id = $this->argument('orderID');
        $order_details = Payment::order($order_id);
        $mailer_data = [];
        $delivered_products = [];

        $rows = DB::table('lz_orders')->select(['product_sku'])
            ->where('order_id', $order_id)
            ->where('status', 'Delivered')
            ->where('email_notification_sent', 0)
            ->get()
            ->toArray();
        $rows = array_column($rows, "product_sku");
        foreach ($order_details['cart']['products'] as $product) {
            if (in_array($product['product_sku'], $rows)) {
                $delivered_products[] = $product;
            }
        }

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

            echo json_encode($mailer_data);
            echo $name_and_company , "\n" . $shipping_addr , "\n";

            $send = Mailer::send_receipt($mailer_data['email'], $mailer_data['name'], $mailer_data, env('MAILER_RECEIPT_ORDER_DELIVERED_TEMPLATE_ID'));
            if ($send['status']) {
                foreach ($delivered_products as $product) {
                    $sku = $product['product_sku'];
                    DB::table('lz_orders')
                        ->where('order_id', $order_id)
                        ->where('product_sku', $sku)
                        ->update([
                            'email_notification_sent' => 1
                        ]);
                }
            }
        }
    }
}
