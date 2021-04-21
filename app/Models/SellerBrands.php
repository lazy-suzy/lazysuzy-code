<?php

namespace App\Models;

use App\Models\Utility;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Auth;
class SellerBrands extends Model
{
    public static $base_site_url = "https://www.lazysuzy.com";
	
	public static function save_sellerbrand($data)
    {
		$error = [];
		$a['status'] = true;

        $is_authenticated = Auth::check();
        $user = Auth::user();

        if(isset($data['name']) && $data['name']=='null'){ 
			$error[] = response()->json(['error' => 'Please enter the name'], 422); 
			$a['status'] = false;
			
				
		}
		else{
				$name = $data['name'];
				$value = substr(trim($data['name']),0,3) ;
		}
		
		
        if(isset($data['headline']) && $data['headline']=='null'){ 
			$error[] = response()->json(['error' => 'Please enter the headline'], 422); 
			 $a['status'] = false; 
				
		}
		else{
				$headline = $data['headline'];
		}
	
        $url =(isset($data['url']) && $data['url']=='null') ? '' : $data['url'];
        $description = (isset($data['description']) && $data['description']=='null') ? '' : $data['description'];
        $location = (isset($data['location']) && $data['location']=='null') ? '' : $data['location']; 
        $user_id = $user->id;

        
        $logo = '';
        if (array_key_exists('logo', $data) && isset($data['logo']) && $data['logo']!='undefined') {

               $a = SellerBrands::is_base64_encoded($data['logo']); return $a;

             	$upload_folder = public_path('public/images/collection');
					 
					$image_name = time() . '-' . Utility::generateID() . '.'. $data['logo']->getClientOriginalExtension() ;
					$uplaod = $data['logo']->move($upload_folder, $image_name); 
					
					  
					
					if($uplaod) {
						$logo = 'images/collection/'.$image_name;
					}
					else 
						$error[] = response()->json(['error' => 'image could not be uploaded. Please try again.'], 422);
					
				 
        }

            if( $a['status']){
      
				 $is_inserted = DB::table('seller_brands')
					->insert([
					'name' =>  $name,
					'value' => $value,
					'headline' => $headline,
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
                'name' => $row->name,
                'value' => $row->value,
                'logo' => "https://www.lazysuzy.com" . $row->logo,
                'url' => $row->url,
                'description' => $row->description,
                'location' => $row->location,
            ]);
        }

        return $brands;
    }
	
	public static function is_base64_encoded($data)
	{ return $data;
		if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
		   return TRUE;
		} else {
		   return FALSE;
		}
	}


}
