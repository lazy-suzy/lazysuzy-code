<?php

namespace App\Http\Controllers\Admin;

use App\Models\Utility;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use App\Models\PromoDiscount;
use App\Models\Collections;

class Dashboard extends Controller
{

    public function filter_products($dept, $cat = null, $subCat = null)
    {
        $isAdminApiCall = true;
        $data = Product::get_filter_products($dept, $cat, $subCat, $isAdminApiCall);
        
       
        foreach($data['products'] as $x => $product) {

            // set variations to null 
            unset($data['products'][$x]['variations']);


            $images = $data['products'][$x]['on_server_images'];
            $images_with_base = [];
            $xbg_primary = explode(",", $data['products'][$x]['xbg_primary']);
            $xbg_secondary = explode(",", $data['products'][$x]['xbg_secondary']);

            $_pri = [];
            $_sec = [];
            // image names without extension
            foreach($xbg_primary as $i) $_pri[] = Utility::get_core_image_name($i);
            foreach ($xbg_secondary as $j) $_sec[] = Utility::get_core_image_name($j);

            $xbg_primary = $_pri;
            $xbg_secondary = $_sec;
           
            foreach($images as $img) {
                $btag = null;
                $image_name = explode("/", $img);
                $image_name = end($image_name);

                // image name without the extension
                $image_name = current(explode(".", $image_name));
                
                foreach($xbg_primary as $i) {
                    //echo "p " . $i . " == " . $image_name . " |\n";
                    if ($i == $image_name) {
                        $btag = 'primary';
                        break;
                    }
                }

                if($btag == null) {
                    foreach ($xbg_secondary as $j) {
                       // echo "s " . $j . " == " . $image_name . " |\n";
                    
                        if ($j == $image_name) {
                            $btag = 'secondary';
                            break;
                        }
                    }
                }

                $images_with_base[] = [
                    'url' => $img,
                    'btag' => $btag
                ];
            }

            $data['products'][$x]['on_server_images'] = $images_with_base;
        }

        return $data;
    }

    public function get_product_details($sku)
    {
        return Product::get_product_details($sku);
    }

    
    public function mark_image(Request $request) {
        $image = Input::post('image');
        $sku = Input::post('product_sku');
        $type = Input::post('type');


        if(strlen($image) > 0 && strlen($sku) > 0 
            && in_array($type, config('admin.image_process'))) {

                $image_process_columns = config('admin.image_process_columns');
                $image_process_folders = config('admin.image_process_folders');

                // incomming image will have domain name at the starting
                // we'll have to remove the domain name
                $image =  str_replace(env('APP_URL'), "", $image);

                $new_image = Utility::make_new_path($image, $type);
                
                $image = public_path() . $image;
                $new_image_path = public_path() . $new_image; 

                // new image path, this will be used for 
                // checking if the folder /westelm/xbgs exists 
                // or not
                $image_path_elements = explode("/", $new_image);
                $image_folder = public_path() . "/" . $image_path_elements[1] . "/" . $image_path_elements[2];
              
                // if folder not exists make folder
                if(!File::exists($image_folder))
                    File::makeDirectory($image_folder);
               
                // if file not exists make file
                if(!File::exists($new_image_path))
                    File::copy($image, $new_image_path);
                
                if(File::exists($new_image_path)) {
                    $db_path = str_replace(public_path(), "", $new_image_path);
                    return [
                        'status' => (bool) Product::mark_image_master_new($sku, $db_path, $image_process_columns[$type]),
                        'image' => $db_path
                    ];
                }
                
            }
            else {
                return response()->json([
                    'message' => 'Unprocessable Entity',
                    'params' => $request->all()
                ], 422);
            }
    }
	
	public function save_collection(Request $request)
    {
		$data = $request->all();
        return Collections::save_collection($data);
    } 
	
	public function save_promocode(Request $request)
    {
		$data = $request->all();
        return PromoDiscount::save_promocode($data);
    }
	

}
