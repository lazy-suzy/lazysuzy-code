<?php

namespace App\Models;

use App\Models\Collections;
use App\Http\Controllers\ProductController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

use Auth;

class Testimonials extends Model
{ 	

    public static function get_testimonials()
	{
		$testimonials = [];
        $rows = DB::table("cust_testimonials")
            ->select("*")
            ->where("status", '1')
            ->get();
 
        foreach ($rows as $row) { 
            array_push($testimonials, [
                "headline" => $row->headline,
                "review" => $row->review,
                "rating" => $row->rating,
                "user_name" => $row->user_name,
                "user_location" => $row->user_location,
                "source" => $row->source,
                "submission_date" => date("F j, Y", strtotime($row->submission_date))
            ]);
        }

        return $testimonials;
	}
	
};