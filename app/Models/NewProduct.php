<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewProduct extends Model
{
    protected $table = "master_new";
    //Change Attribute names of timestamps
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $fillable = ['status'];
    protected $casts = [
        'product_dimension' => 'array',
    ];
}
