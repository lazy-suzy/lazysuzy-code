<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deals extends Model
{
    protected $table = 'site_deals';
    public static function get_deals()
    {
        return Deals::where('is_active', 1)->orderBy('rank', 'ASC')->get();
    }
}
