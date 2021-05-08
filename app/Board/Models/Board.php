<?php

namespace App\Board\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

use App\Board\Scopes\AuthAndActiveScope;
use Illuminate\Support\Facades\DB;

class Board extends Model
{    
  protected $table = 'board';
  protected $primaryKey = 'board_id';
  protected $primarySearchKey = 'uuid';
  protected $fillable = ['state', 'title', 'preview', 'type_room', 'type_style', 'type_privacy', 'is_published', 'is_active'];
  protected static function boot() {
    parent::boot();
    static::addGlobalScope(new AuthAndActiveScope);
    static::creating(function($board){
      $board->uuid = Board::randomId(6);
    });
    static::saving(function($board){
      $board->user_id = Auth::id();
    });
  }
  
  public function setIsLikedAttribute($value) {
    $this->attributes['is_liked'] = $value;
  }

  private static function randomId($length = 10) {

     $str_result = '0123456789abcdefghijklmnopqrstuvwxyz'; 
     $id = substr(str_shuffle($str_result), 0, $length);
     
     $validator = \Validator::make(['uuid'=>$id],['uuid'=>'unique:board']);
     
     if($validator->fails())
          return $this->randomId();

     return $id;
  }
  
  public function scopeID($query, $boardID) {
    if($boardID)
      return $query->where($this->primarySearchKey, $boardID);
  }

  public static function board($id = null) {
    $boards = Board::id($id)->get();
    // $id ?: $boards->makeHidden(['state']);
    return $boards;
  }

  public static function get_board_by_username($username) {
    $cols = ['board_id', 'user_id', 'uuid', 'state', 
      'title', 'preview', 'type_room', 'type_style', 
      'type_privacy', 'is_published', 'is_active', 
      'board.created_at', 'board.updated_at'];
    
      $boards = DB::table('board')
        ->select($cols)
        ->where('users.username', $username)
        ->join('users', 'users.id', '=', 'board.user_id')
        ->get();
    
    return $boards;
  }

  public static function all_boards() {
    $boards = Board::withoutGlobalScopes()
      ->where('type_privacy', 2)
      ->where('is_published', 1)
      ->where('is_active', 1)->get();

    foreach($boards as &$board) {
      $board->is_liked = BoardLikes::is_board_liked($board->uuid, Auth::id());
      $board->like_count = BoardLikes::get_board_likes($board->uuid);
    }
    
    return $boards;
  }
}
