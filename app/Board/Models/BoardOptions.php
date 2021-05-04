<?php

namespace App\Board\Models;

use Illuminate\Database\Eloquent\Model;

class BoardOptions extends Model
{
    
    protected $table = 'lz_board_options';

    public static function get_all() {
        $rows = BoardOptions::select(['image', 'type'])
            ->where('is_active', 1)->get();
        $res = [
            'thumbs' => [],
            'images' => []   
        ];
        foreach($rows as $row) {
            if($row->type == 't') 
                $res['thumbs'][] = $row->image;
            else
                $res['images'][] = $row->image;
        }

        return $res;
    }
}
