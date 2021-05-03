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
		$bnamefolder = 'brand';

        $is_authenticated = Auth::check();
        $user = Auth::user();
		
        if( $data['name']==''  && $data['name']==null){ 
			$error[] = response()->json(['error' => 'Please enter the name','key'=>'name'], 422); 
			$a['status'] = false;
			
				
		}
		else{
				$name = $data['name'];
				$value = substr(trim($data['name']),0,3) ;
				$bnamefolder = str_replace(' ', '', $data['name']);
		}
		
		
        if($data['headline']==''  && $data['headline']==null){ 
			$error[] = response()->json(['error' => 'Please enter the headline','key'=>'headline'], 422); 
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

               $imagedata = SellerBrands::is_base64_encoded($data['logo']); 
			   
			   if($imagedata==1){
 
				$upload_folder = '/var/www/html/seller/';
				$mode = 0777;
				@mkdir($upload_folder . $bnamefolder . "/logo/", $mode, true);
					 
					$image_name = time() . '-' . Utility::generateID() . '.'. $data['logo']->getClientOriginalExtension() ;
					$uplaod = $data['logo']->move($upload_folder. $bnamefolder . '/logo/', $image_name); 
					
					  
					
					if($uplaod) {
						$logo = '/seller/' . $bnamefolder . '/logo/'.$image_name;
					}
					else 
						$error[] = response()->json(['error' => 'image could not be uploaded. Please try again.','key'=>'logo'], 422);
			   }
			   else{
			   
							$logo = substr($data['logo'], strrpos($data['logo'], '/') + 1);
							$logo = '/seller/' . $bnamefolder . '/logo/'.$logo;
			   }
					
				 
        }  
            if( $a['status']){
       
				
				$querybrand = DB::table('seller_brands')->select(DB::raw('COUNT(id) as brandid'))->where('user_id', '=', $user_id)->get();
				 
				if( $querybrand[0]->brandid > 0){
					
						$is_inserted =  DB::table('seller_brands')
									->where('user_id', $user_id)
									->update([
												'name' =>  $name,
												'value' => $value,
												'headline' => $headline,
												'url' => $url,
												'description' => $description, 
												'location' => $location, 
												'logo' => $logo,
												'is_active' => '1'
						]);
						
						$prod_update = DB::table('seller_products')
									->where('submitted_id', $user_id)
									->update([
												'brand' =>  $value
						]);
					
				}
				else{
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
				}
				if ($is_inserted == 1) {
					
						/*if( $querybrand[0]->brandid > 0){
							$is_inserted =  DB::table('master_brands')
									->where('user_id', $user_id)
									->update([
												'name' =>  $name,
												'value' => $value,
												'headline' => $headline,
												'url' => $url, 
												'logo' => $logo,
												'description' => $description, 
												'location' => $location,
												'is_active' => '1'
						]);
					}
					else{*/
							$is_inserted = DB::table('master_brands')
								->insert([
								'name' =>  $name,
								'value' => $value,
								'headline' => $headline,
								'url' => $url,
								'logo' => $logo,
								'description' => $description,
								'location' => $location, 
								'is_active' => '1'
							]);
							
					//}
					
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
                'headline' => $row->headline,
            ]);
        }

        return $brands;
    }
	
	public static function is_base64_encoded($data)
	{ 
		if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
		   return TRUE;
		} else {
		   return FALSE;
		}
	}


}
