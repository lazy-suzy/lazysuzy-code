<?php

namespace App\Models;

use Hamcrest\Type\IsString;
use Illuminate\Database\Eloquent\Model;

class SalesTax extends Model
{
    protected $table = "lz_sales_tax";

    public static function get_sales_tax($state_code) {
        $tax = 0;
        $shipping = false;
        $row = SalesTax::where('state_code', $state_code)->get();
        
        if(isset($row[0])) {
            $tax = $row[0]->tax_rate;
            $shipping = $row[0]->shipping;
        }

        return [floatval($tax), (bool)$shipping];
    }
}
