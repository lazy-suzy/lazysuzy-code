<?php

namespace App\Models;

use App\Models\Utility;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class SellerBrands extends Model
{
    public static $base_site_url = "https://www.lazysuzy.com";
	
	public static function save_sellerbrand($data)
    {


        $is_authenticated = Auth::check();
        $user = Auth::user();

        $name = empty($data['name']) ? '' : $data['name'];
        $value = empty($data['name']) ? '' : substr(trim($data['name']), 0, 3);
        $url = empty($data['url']) ? '' : $data['url'];
        $description = empty($data['description']) ? '' : $data['description'];
        $location = empty($data['location']) ? '' : $data['location']; 
        $user_id = $user->id;

        
        $logo = '';
        if (array_key_exists('logo', $data) && isset($data['logo'])) {

            

             	$upload_folder = public_path('public/images/collection');
					 
					$image_name = time() . '-' . Utility::generateID() . '.'. $data['logo']->getClientOriginalExtension() ;
					$uplaod = $data['logo']->move($upload_folder, $image_name); 
					
					  
					
					if($uplaod) {
						$logo = 'images/collection/'.$image_name;
					}
					else 
						$error[] = response()->json(['error' => 'image could not be uploaded. Please try again.'], 422);
					
				 
        }




        $is_inserted = DB::table('seller_brands')
            ->insert([
                'name' =>  $name,
                'value' => $value,
                'url' => $url,
                'description' => $description,
                'user_id' => $user_id,
                'location' => $location, 
                'logo' => $logo,
                'is_active' => '1'
            ]);
        if ($is_inserted == 1) {
            $a['status'] = true;
        } else {
            $a['status'] = false;
        }

        $a['errors'] = $error;

        return $a;
    }
	
	public static function get_all()
    {
		$is_authenticated = Auth::check();
        $user = Auth::user();
		$user_id = $user->id;
		
        $rows = DB::table("seller_brands")->select("*"); 
        $rows = $rows->where('is_active', 1)->where('user_id', $user_id)->get()
            ->toArray();;

        $brands = [];

        foreach ($rows as $row) {
            array_push($brands, [
                'name' => $row['name'],
                'value' => $row['value'],
                'logo' => $base_site_url . $row['logo'],
                'url' => $row['url'],
                'description' => $row['description'],
                'location' => $row['location'],
            ]);
        }

        return $brands;
    }


}
