<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CollectionFilter extends Model
{

    public static function apply($query, $all_filters) {

        if(!isset($all_filters['collection']) 
            || sizeof($all_filters['collection']) == 0) {
                return $query;
            }
        
        $query = $query->whereRaw('collection REGEXP "' . implode("|", $all_filters['collection']) . '"');
        return $query;

    }
}
