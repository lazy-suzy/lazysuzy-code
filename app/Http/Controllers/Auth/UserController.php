<?php

namespace App\Http\Controllers\Auth;

use Dotenv\Parser;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\SocialIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Models\UserVisits;
use App\Models\Utility;
use Dotenv\Validator as DotenvValidator;
use Illuminate\Contracts\Validation\Validator as ValidationValidator;
use Illuminate\Support\Facades\Auth;
use Validator;

class UserController extends Controller
{
    public $successStatus = 200;

    public function explodeX($delimiters, $string) {
        return explode(chr(1), str_replace($delimiters, chr(1), $string));
    }

    public function create_username($email, $name) {
      $email_head = explode("@", $email)[0];
      $names = $this->explodeX([' ', '_'], $name);

      $f_name = isset($names[0]) ? $names[0] : null;
      $l_name = isset($names[1]) ? $names[1] : null;

      $username = $email_head . '-' . rand(0,999);

      if(isset($f_name) && isset($l_name))
        $username = strtolower($f_name) . strtoupper($l_name[0]) . '-' . rand(0, 999);

      $validator = Validator::make(['username' => $username], [
        'username' => 'unique:users|alpha_dash'
      ]);

      while($validator->fails()) {
        $username = $username . '-' . rand(0, 9);
        $validator = Validator::make(['username' => $username], [
          'username' => 'unique:users|alpha_dash'
        ]);
      }

      return $username;
    }
    public function findOrCreateUser($providerUser, $provider) {
        $auth_user = null;
        $account = SocialIdentity::whereProviderName($provider)
            ->whereProviderId($providerUser->getId())
            ->first();
        if ($account && $account->user) {
            $auth_user = $account->user;
            Auth::login($auth_user, true);
            $user = Auth::user();
            return $user;

        } else {
            //return $this->register(null, $providerUser);
            $user = User::whereEmail($providerUser->getEmail())->first();
            $f_l_name = $this->explodeX(array(' ', '_'), $providerUser->getName());

           
            if (!$user) {
                $user = User::create([
                    'email' => $providerUser->getEmail(),
                    'name'  => $providerUser->getName(),
                    'password' => $provider,
                    'first_name' => $f_l_name[0],
                    'last_name' => isset($f_l_name[1]) ? $f_l_name[1] : "",
                    'gender' => 'default',
                    'oauth_provider' => $provider,
                    'oauth_uid' => $providerUser->getId(),
                    'picture' => $providerUser->getAvatar(),
                    'username' => $this->create_username($providerUser->getEmail(), $providerUser->getName()),
                    'locale' => 'en',
                ]);

                $user->identities()->create([
                    'provider_id'   => $providerUser->getId(),
                    'provider_name' => $provider,
                ]);

                $auth_user =  $user;
                $auth_user['access_token'] = $user->createToken('lazysuzy-web')->accessToken;
                Auth::login($user);
                return $auth_user;
            }
            else {
              Auth::login($user);
              return $user;
            }

            return null;
        }

        return null;
    }
    public function login() {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();
            $success['token'] =  $user->createToken('lazysuzy-web')->accessToken;
            return response()->json([
                'success' => $success,
                'user' => $user
            ], $this->successStatus);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }
    public function register(Request $request) {
        $user = false;
        $data = $request->all();
        
        // register a type guest user
        if(isset($data['guest'])) {
          $user = User::create([
            'name' => '',
            'email' => '',
            'password' => '',
            'first_name' => '',
            'last_name' => '',
            'oauth_provider' => '',
            'oauth_uid' => '',
            'user_type' => config('user.user_type.guest')
          ]);
        }
        // register a regular user
        else{
          $validator = Validator::make($data, [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
          ]);
          
          if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
          }
          
          $name = explode(' ', $data['name']);
          $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'first_name' => isset($name[0]) ? $name[0] : '',
            'last_name' => isset($name[1]) ? implode(' ', array_slice($name, 1)) : '',
            'oauth_provider' => 'basic',
            'oauth_uid' => rand(0, 100),
            'username' => $this->create_username($data['email'], $data['name']),
            'user_type' => config('user.user_type.default')
          ];
          
          Auth::shouldUse('api');
          // check if the registration request coming from an already authenticated user
          if(Auth::check()) {
            $user = Auth::user();
            // check if guest using is trying to convert
            if(isset($user->user_type) && $user->user_type == config('user.user_type.guest')){
              // there was some problem in upgrading the user
              if(!$user->update($userData)){
                return response()->json(['error' => ['update' => "unable to update user data"]], 401);
              }
              // invalidate all previous session for this user
              $this->logout($request, true);
            }
            // check if a regular user is trying to create another account
            else if($user->user_type == config('user.user_type.default')){
              $user = User::create($userData);
            }
          }
          else
            $user = User::create($userData);
        }
        
        if($user){
          $success['token'] = $user->createToken('lazysuzy-web')->accessToken;  
          $success['user'] =  $user;
          return response()->json(['success' => $success], $this->successStatus);
        }
        else
          return response()->json(['error' => ['error' => "unable to register the user"]], 401);
    }
    public function logout(Request $request, $fromEverywhere = false) {
      if($fromEverywhere)
        foreach ($request->user()->tokens as $token) {
            $token->revoke();
        }
      else{
        $currentToken = $request->bearerToken();
        $tokenId = (new \Lcobucci\JWT\Parser())->parse($currentToken)->getHeader('jti');
        $request->user()->tokens->find($tokenId)->revoke();
      }
      return response()->json(['status' => true], 204);
    }

    public function update(Request $request) {
        $data = $request->all();
        
        // in order to update the user must be logged in
        if (Auth::check()) {
            $user = Auth::user();

            if (isset($data['name'])) {
                $name = explode(' ', $data['name']);
                $user->first_name = isset($name[0]) ? $name[0] : '';
                $user->last_name = isset($name[1]) ? implode(' ', array_slice($name, 1)) : '';
                $user->name = $data['name'];
            }

            if (isset($data['email'])) {
                $validator = Validator::make($data, ['email' => ['required', 'string', 'email', 'max:255', 'unique:users']]);

                if ($validator->fails())
                    return response()->json(['error' => $validator->errors()], 401);

                $user->email = $data['email'];
            }

            // set the password only if does not already exists
            if (isset($data['password']) && empty($user->password) && $user->user_type == config('user.user_type.guest')) {
                $validator = Validator::make($data, ['password' => ['required', 'string', 'min:8']]);

                if ($validator->fails())
                    return response()->json(['error' => $validator->errors()], 401);

                $user->password = Hash::make($data['password']);
            }

            // if both email and password exist and type is guest then the user is trying to signup
            if (isset($data['password']) && isset($data['email']) && $user->user_type == config('user.user_type.guest')) {
             
              // reset SKU visits 
              //UserVisits::reset_visits($user->id);
              return $this->register($request);
            }
            else {
              if ($user->update()) {
                  $success['token'] =  $request->bearerToken();  
                  $success['user'] =  $user;
                  return response()->json(['success' => $success], $this->successStatus);
              } else
                  return response()->json(['error' => ['error' => "unknown error"]], 401);
            }
        }
    }
    public function keepAlive() {
      Auth::shouldUse('api');
      return response()->json(['alive' => Auth::check()], $this->successStatus);
    }

    public function details_update(Request $request) {
      $data = $request->all();
      $validator = null;
      $user = Auth::user(); // get the user


      // allow users to save empty values in the following fields
      // update info if request has the attrs
      if(array_key_exists('description', $data) 
        && (isset($data['description']) || strlen($data['description']) == 0))
        $user->description = $data['description'];
      
      if (array_key_exists('location', $data) 
        && (isset($data['location']) || strlen($data['location']) == 0))
        $user->location = $data['location'];
      
      if (array_key_exists('tag_line', $data)
        && (isset($data['tag_line']) || strlen($data['tag_line']) == 0))
        $user->tag_line = $data['tag_line'];
      
      // update data once that does not require validation
      $user->update();

      // validated data will be updated seperately so that if 
      // any data fails the validation all the other info 
      // sent in the request is updated
      $error = [];
      if (array_key_exists('website', $data)
          && (isset($data['website']) || strlen($data['website']) == 0)) {
          
          $user->website = $data['website'];
          $user->update();
        
      }

      if (array_key_exists('username', $data)
          && (isset($data['username']) && strlen($data['username']) > 0)) {
        $validator = Validator::make($data, [
          'username' => 'unique:users|alpha_dash'
        ]);

        if ($validator->fails())
          $error[] = response()->json(['error' => $validator->errors()], 422);
        else {
          $user->username = $data['username'];
          $user->update();
        }
      }

      if(array_key_exists('image', $data)
        && isset($data['image'])) {
        $validator = Validator::make($data, [
          'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails())
          $error[] = response()->json(['error' => $validator->errors()], 422);
        else {
          // uplaod image
          $upload_folder = public_path('uimg');
          $image_name = time() . '-' . Utility::generateID() . '.' . request()->image->getClientOriginalExtension();
          $uplaod = request()->image->move($upload_folder, $image_name);

          if($uplaod) {
            $user->picture = '/uimg/' . $image_name;
            $user->update();
          }
          else 
            $error[] = response()->json(['error' => 'image could not be uploaded. Please try again.'], 422);
         
        }
      }
      
      return [
        'errors' => $error, 
        'user' => Auth::user()
      ];
        
    }
}
