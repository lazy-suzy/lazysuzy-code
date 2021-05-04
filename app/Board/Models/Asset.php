<?php

namespace App\Board\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use App\Board\Scopes\AuthAndActiveScope;

class Asset extends Model
{
  protected $table = 'asset';
  protected $primaryKey = 'asset_id';
  protected $fillable = ['name', 'price', 'brand', 'path', 'listing_url', 'tags', 'is_private', 'is_active'];
  
  protected static function boot() {
    parent::boot();
    static::addGlobalScope(new AuthAndActiveScope);
    static::saving(function($asset){
      $asset->user_id = Auth::id();
    });
  }
  
  public function scopeID($query, $assetID) {
    if($assetID)
      return $query->where($this->primaryKey, $assetID);
  }

  public static function asset($id = null) {
    return Asset::id($id)->get();
  }
}
