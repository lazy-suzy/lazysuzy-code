<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Search extends Model
{
    protected $table = 'lz_search_keywords';
    protected $hidden = ['id', 'updated_at', 'created_at'];

    public static function get_all() {
        $rows = Search::all()->toArray();
        $rows = array_column($rows, 'value', 'search_key');
        
        // convert json strings back to array
        $rows = array_map(function($json_string) { 
                    return json_decode($json_string);
                }, $rows);

        //$rows = [];
        return $rows;
    }
}
