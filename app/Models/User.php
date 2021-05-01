<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;



class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'first_name', 'last_name', 'gender', 
        'oauth_provider', 'oauth_uid', 'picture', 'locale', 'user_type',
        'description', 'username', 'tag_line', 'website', 'location'
    ];
    
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class , 'user_wishlists','user_id' , 'product_id');
    }
    public function identities()
    {
   		return $this->hasMany('App\Models\SocialIdentity');
    }


}
