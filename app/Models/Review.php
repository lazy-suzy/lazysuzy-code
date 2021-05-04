<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Validator;
use Auth;

class Review extends Model
{
  	 
	 public static function save_product_review($data) {
		
		
		$is_authenticated = Auth::check();
			$user = Auth::user(); 
		
		
		if(!isset($data['user_location'])){
			$data['user_location']='null';
		}
		if(!isset($data['headline'])){
			$data['headline']='null';
		}
		if(!isset($data['review'])){
			$data['review']='null';
		}
		 $validator = null;
		 $imglist = '';
		  $error = [];
		if(array_key_exists('rimage', $data) && isset($data['rimage'])) {
			
					$upload_folder = public_path('images/uimg');
					for($i=0;$i<count($data['rimage']);$i++){
					$image_name = time() . '-' . Utility::generateID() . '.'. $data['rimage'][$i]->getClientOriginalExtension() ;
					$uplaod = $data['rimage'][$i]->move($upload_folder, $image_name);
					$imglist .= 'images/uimg/'.$image_name.',,';
					} 
					
					if($uplaod) {
						//$user->picture = '/uimg/' . $image_name;
						//$user->update();
					}
					else 
						$error[] = response()->json(['error' => 'image could not be uploaded. Please try again.'], 422);
		}
		 $datetime = date("Y-m-d H:i:s");
		
		/*						
			'count_helpful' => $data['count_helpful'],
			'count_reported' => $data['count_reported'],
			'source' => $data['source'],
		*/
		
		 $is_inserted = DB::table('user_reviews')
                    ->insert([
								'user_id' =>  $user->id,
								'product_sku' => $data['product_sku'],
								'headline' => $data['headline'],
								'review' => $data['review'],
								'rating' => $data['rating'],
								'review_images' => $imglist,
								'user_name' => $data['user_name'], 
								'user_email' => $data['user_email'],
								'user_location' => $data['user_location'],
								'status' => $data['status'],
								'submission_time' => $datetime
							]);
		if($is_inserted==1){
			$a['status']=true;
		}
		else{
			$a['status']=false;
		}
		
		$a['errors'] = $error;
	
        return $a;

     
        
    }
	
	
	 
	 public static function get_product_review($sku,$limit){ //return $sku;
		$all_reviews = [];
		$highest_reviews = [];
		$lowest_reviews = [];
		$reviews = [];
		
        $rows = DB::table("master_reviews")
            ->select(['review_id','product_sku','headline','review','rating','review_images','user_name','user_location','status','count_helpful','count_reported','users_helpful','users_reported','source','submission_time'])
            ->where('product_sku', '=', $sku)
			->where('status','>=', '2')
			->where('headline','!=', NULL)
			->where('review','!=', NULL)
            ->orderBy("submission_time", "DESC")
			 ->limit($limit)
            ->get(); 
		//	echo $rows->toSql();die;
		foreach ($rows as $row){
			if($row->status==3){
				$row->statusmsg='Verified Purchase';			
			}
			else{
				$row->statusmsg='';	
			}
			$row->submission_time = date("F j, Y", strtotime($row->submission_time));
			$helpfuluser_str = $row->users_helpful;
			if($helpfuluser_str!='' || $helpfuluser_str!=NULL)
			{	
				$helpfuluser_arr = explode(",",$helpfuluser_str);
				$row->helpful_user_count = count($helpfuluser_arr);
			}
			else{
				$row->helpful_user_count = 0;
			}
			
				
			$reporteduser_str = $row->users_reported;
			if($reporteduser_str!='' || $reporteduser_str!=NULL)
			{	
				$reporteduser_arr = explode(",",$reporteduser_str);
				$row->reported_user_count = count($reporteduser_arr);
			}
			else{
				$row->reported_user_count = 0;
			}	
            array_push($all_reviews, $row);
	    }
		 
        $rows = DB::table("master_reviews")
            ->select(['review_id','product_sku','headline','review','rating','review_images','user_name','user_location','status','count_helpful','count_reported','users_helpful','users_reported','source','submission_time'])
            ->where('product_sku', '=', $sku)
			->where('status','>=', '2')
			->where('headline','!=', NULL)
			->where('review','!=', NULL)
            ->orderBy("rating", "DESC")
			 ->limit($limit)
            ->get(); 
			
		foreach ($rows as $row){
			if($row->status==3){
				$row->statusmsg='Verified Purchase';			
			}
			else{
				$row->statusmsg='';	
			}
			$row->submission_time = date("F j, Y", strtotime($row->submission_time));
			$helpfuluser_str = $row->users_helpful;
			if($helpfuluser_str!='' || $helpfuluser_str!=NULL)
			{	
				$helpfuluser_arr = explode(",",$helpfuluser_str);
				$row->helpful_user_count = count($helpfuluser_arr);
			}
			else{
				$row->helpful_user_count = 0;
			}
			
			$reporteduser_str = $row->users_reported;
			if($reporteduser_str!='' || $reporteduser_str!=NULL)
			{	
				$reporteduser_arr = explode(",",$reporteduser_str);
				$row->reported_user_count = count($reporteduser_arr);
			}
			else{
				$row->reported_user_count = 0;
			}
            array_push($highest_reviews, $row);
	    }
		
		
        $rows = DB::table("master_reviews")
            ->select(['review_id','product_sku','headline','review','rating','review_images','user_name','user_location','status','count_helpful','count_reported','users_helpful','users_reported','source','submission_time'])
            ->where('product_sku', '=', $sku)
			->where('status','>=', '2')
			->where('headline','!=', NULL)
			->where('review','!=', NULL)
             ->orderBy("rating", "ASC")
			 ->limit($limit)
            ->get(); 
			
		foreach ($rows as $row){
			if($row->status==3){
				$row->statusmsg='Verified Purchase';			
			}
			else{
				$row->statusmsg='';	
			}
			$row->submission_time = date("F j, Y", strtotime($row->submission_time));
			$helpfuluser_str = $row->users_helpful;
			if($helpfuluser_str!='' || $helpfuluser_str!=NULL)
			{	
				$helpfuluser_arr = explode(",",$helpfuluser_str);
				$row->helpful_user_count = count($helpfuluser_arr);
			}
			else{
				$row->helpful_user_count = 0;
			}
			
			$reporteduser_str = $row->users_reported;
			if($reporteduser_str!='' || $reporteduser_str!=NULL)
			{	
				$reporteduser_arr = explode(",",$reporteduser_str);
				$row->reported_user_count = count($reporteduser_arr);
			}
			else{
				$row->reported_user_count = 0;
			}
			
            array_push($lowest_reviews, $row);
	    } 
	  
		//$count_rating = DB::table('master_reviews')->select(DB::raw('COUNT(id) as cnt_rating'))->where('product_sku', '=', $sku)->where('status','>=', '2')->where('headline','!=', NULL)->where('review','!=', NULL)->get();	
			//return  $count_rating;
		//print_r($count2);
	 	//$tot_rating = DB::table('master_reviews')->select(DB::raw("SUM(rating) as cnt"))->where('product_sku', '=', $sku)->where('status','>=', '2')->where('headline','!=', NULL)->where('review','!=', NULL)->get();

		
		$reviews['all_reviews']= $all_reviews;
		$reviews['highest_reviews']= $highest_reviews;
		$reviews['lowest_reviews']= $lowest_reviews;
		//$reviews['count_rating']= $count_rating[0]->cnt_rating;
		//$reviews['tot_rating']= $tot_rating[0]->cnt;
		
        return $reviews; 
	 
	 }
	
	   public static function get_all_review($sku)
    {
        $perPage = 12; 
        $RATING_ASC = "rating_low_to_high";
        $RATING_DESC = "rating_high_to_low"; 

 

        // getting all the extra params from URL to parse applied filters
        $page_num    = Input::get("pageno");
        $limit       = Input::get("limit");
        $sort_type   = Input::get("sort_type");

        $all_filters = [];
        $query       = DB::table('master_reviews')->where('product_sku', '=', $sku)->where('status','>=', '2')->where('headline','!=', NULL)->where('review','!=', NULL);

        if (!isset($limit)) {
            $limit = $perPage;
        }

        $start = $page_num * $limit;

        // 7. sort_type
        if (isset($sort_type)) {

            if ($sort_type == $RATING_ASC) {
                $query = $query->orderBy('rating', 'asc');
            } else if ($sort_type == $RATING_DESC) {
                $query = $query->orderBy('rating', 'desc');
            }else {
                $query = $query->orderBy('submission_time', 'desc');
            }
        }
        else { 
                $query = $query->orderBy('submission_time', 'desc');
        }

      
        $query = $query->offset($start)->limit($limit);
		$query = $query->get(); 
		$all_reviews = [];
		foreach ($query as $row){
			if($row->status==3){
				$row->statusmsg='Verified Purchase';			
			}
			else{
				$row->statusmsg='';	
			}
			$row->submission_time = date("F j, Y", strtotime($row->submission_time));
			$helpfuluser_str = $row->users_helpful;
			if($helpfuluser_str!='' || $helpfuluser_str!=NULL)
			{	
				$helpfuluser_arr = explode(",",$helpfuluser_str);
				$row->helpful_user_count = count($helpfuluser_arr);
			}
			else{
				$row->helpful_user_count = 0;
			}
			
			$reporteduser_str = $row->users_reported;
			if($reporteduser_str!='' || $reporteduser_str!=NULL)
			{	
				$reporteduser_arr = explode(",",$reporteduser_str);
				$row->reported_user_count = count($reporteduser_arr);
			}
			else{
				$row->reported_user_count = 0;
			}
			
            array_push($all_reviews, $row);
	    } 
		
		return $all_reviews; 
  
    }

	
	public static function mark_helpful_review($data){
		$review_id = $data['review_id'];
		$user_id = $data['user_id'];
		$helpfuluser_arr = [];
		$flag = 0;
		$insertedstr = 	$user_id;
		$getrow = DB::table("master_reviews")
           ->select(['review_id','product_sku','headline','review','rating','review_images','user_name','user_location','status','count_helpful','count_reported','users_helpful','users_reported','source','submission_time'])
            ->where('id', '=', $review_id)
            ->get(); 
			
		if (isset($getrow[0])) 
		{	
				$helpfuluser_str = $getrow[0]->users_helpful;
				if($helpfuluser_str!='' || $helpfuluser_str!=NULL)
				{	
					$helpfuluser_arr = explode(",",$helpfuluser_str);
				
					if (in_array($user_id, $helpfuluser_arr))
					{
						$a['status']=false;
						$a['msg']='User Id Exist';
					}
					else
					{
						$flag = 1; 
						$insertedstr = 	$helpfuluser_str.','.$user_id;
					}
				}
				else
				{
					$flag = 1; 
				}
				
				if($flag)
				{
					
					$is_insert= DB::table('master_reviews')
								->where('id', $review_id)
								->update(['users_helpful' => $insertedstr]);
								
					$a['status']=true;
					$a['msg']='User Id Inserted Successfully';
						
						
					
				}
			
		}
		else
		{
				$a['status']=false;
				$a['msg']='No Review found';
		}
		return $a;
	}
   
    public static function mark_reported_review($data){
		$review_id = $data['review_id'];
		$user_id = $data['user_id'];
		$reporteduser_arr = [];
		$flag = 0;
		$insertedstr = 	$user_id;
		$getrow = DB::table("master_reviews")
            ->select(['review_id','product_sku','headline','review','rating','review_images','user_name','user_location','status','count_helpful','count_reported','users_helpful','users_reported','source','submission_time'])
            ->where('id', '=', $review_id)
            ->get(); 
			
		if (isset($getrow[0])) 
		{	
				$reporteduser_str = $getrow[0]->users_reported;
				if($reporteduser_str!='' || $reporteduser_str!=NULL)
				{	
					$reporteduser_arr = explode(",",$reporteduser_str);
				
					if (in_array($user_id, $reporteduser_arr))
					{
						$a['status']=false;
						$a['msg']='User Id Exist';
					}
					else
					{
						$flag = 1; 
						$insertedstr = 	$reporteduser_str.','.$user_id;
					}
				}
				else
				{
					$flag = 1; 
				}
				
				if($flag)
				{
					
					$is_insert= DB::table('master_reviews')
								->where('id', $review_id)
								->update(['users_reported' => $insertedstr]);
								
					$a['status']=true;
					$a['msg']='User Id Inserted Successfully';
						
						
					
				}
			
		}
		else
		{
				$a['status']=false;
				$a['msg']='No Review found';
		}
		return $a;
	} 
}