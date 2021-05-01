<?php

namespace App\Board\Controllers;

use App\Http\Controllers\Controller;
use App\Board\Models\Asset;
use App\Board\Models\Board;
use App\Board\Models\BoardLikes;
use App\Board\Models\BoardOptions;
use App\Models\Utility;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class BoardController extends Controller 
{
    public static function get_board(Request $request, $id = null) {

        $data = $request->all();
        $username = isset($data['username']) ? $data['username'] : null;

        $validator = Validator::make($data, [
          'username' => 'alpha_dash'
        ]);

        if($validator->fails())
          return response()->json(['error' => $validator->errors()], 422);

        if(!isset($username)) $board = Board::board($id);
        else $board = Board::get_board_by_username($username);

        $user = Auth::id();
        foreach ($board as &$b) {
          $b->is_liked = BoardLikes::is_board_liked($b->uuid, $user);
          $b->like_count = BoardLikes::get_board_likes($b->uuid);
        }
        
        return $board;
    }

    public static function get_board_for_preview($id) {
      Auth::shouldUse('api');
      $board =  Board::withoutGlobalScopes()
              ->id($id)
              ->where(function ($query) {
                $query->orWhere('user_id', '=', Auth::check() ? Auth::id() : 0);
                $query->orWhere('type_privacy', '>', 0);
              })
              ->get();
      
      if(isset($board[0])) {
        $board[0]['is_liked'] = BoardLikes::is_board_liked($id, Auth::id());
        $board[0]['like_count'] = BoardLikes::get_board_likes($id);

        // get board owner info (it is not always the Auth::user())
        $row = Board::withoutGlobalScopes()->where('uuid', $id)  
          ->join("users", "board.user_id", "=", "users.id");

        $row = $row->get();

        if(isset($row[0])) {
          $row = $row[0];
          $board[0]['username'] = $row->username;
          $board[0]['picture'] = $row->picture;
        }
      }

      return $board;
    }
    
    public static function get_asset($id = null) {
      return Asset::asset($id);

    }
    
    public static function get_asset_for_preview() {
      Auth::shouldUse('api');
      return Asset::withoutGlobalScopes()
              ->where('user_id', '=', Auth::check() ? Auth::id() : 0)
              ->orWhere('is_private', '=', 0)
              ->get();
    }
    
    public static function update_board(Request $request, $id = null) {
        $data = $request->all();
        
        if($id){
          $board = Board::board($id);
          if($board){
            if(isset($data['preview']) && BoardController::is_base64_encoded($data['preview'])){
              $filename = "public/" . $id . '.png';
              if(Storage::put($filename, file_get_contents($data['preview'])))
                $data['preview'] = Storage::url($filename);
            }
            $board->first()->update($data);
            return $board;
          }
        }
        else
          return Board::create($data);
    } 

    public static function update_asset(Request $request, $id = null) {

      $data = $request->all();
      $client = new \GuzzleHttp\Client(['verify' => false]);
        
      $validator = Validator::make($data, [
        'is_private' => 'boolean',
        'is_active' => 'boolean',
        'file' => 'sometimes|image',
        'url' => 'sometimes|url',
        'transparent' => 'sometimes|boolean'
      ]);
      
      if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()->all()], 401);
      }
      
      // image was uploaded using URL
      if(isset($data['url'])){
        $response = $client->get($data['url']);
        if($response->getStatusCode() == 200){
          $data['path'] = self::store_content($response->getBody());
        }
      }
      // image was uploaded using file
      else if(isset($data['file'])){
        $data['path'] = Storage::url(Storage::putFile('public', $request->file('file')));
      }
      if($id){
        $asset = Asset::findOrFail($id);
        // check if transparent request is made
        if(isset($data['transparent'])){
          $response = $client->post(env('REMOVEBG_API_ENDPOINT'), [
            'headers' => ['X-API-Key' => env('REMOVEBG_API_KEY')],
            'form_params' => [
              'image_url' => asset($asset->path),
              'format' => 'png',
            ]
          ]);
          $asset->transparent_path = self::store_content($response->getBody());
        }
        $asset->update($data);
        return $asset;
      }
      else
        return Asset::create($data);
    }
    
    private static function store_content($content) {
      $filename = "temp" . DIRECTORY_SEPARATOR . Str::random();
      Storage::put($filename, $content);
      $path = Storage::putFile('public', new File(storage_path('app') .DIRECTORY_SEPARATOR. $filename));
      Storage::delete($filename);
      return Storage::url($path);
    }
    
    private static function is_base64_encoded($data) {
      return (strpos($data, 'data:image/png;base64') === 0);
    }

    public function like_board($board_id) {

        $user = Auth::user();
        if(isset($board_id))
          return response()->json(BoardLikes::like_board($board_id, $user->id), 200);
        
        return response()->json(['error' => 'Invalid Board'], 422);
    }

    public function unlike_board($board_id) {
      $user = Auth::user();
      if(isset($board_id))
        if( (bool)BoardLikes::unlike_board($board_id, $user->id) )
          return response()->json(['message' => 'Board unliked'], 200);
        else
          return response()->json(['message' => 'Could not unlike board'], 200);

      return response()->json(['error' => 'Invalid Board'], 422);
    }  
    
    public function get_all_boards() {
      return Board::all_boards();
    }
    
    public function get_all_options() {
      return BoardOptions::get_all();
    }
}
