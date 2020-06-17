<?php

namespace App\Http\Controllers\Auth;

use Dotenv\Parser;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\SocialIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Validator;

class UserController extends Controller
{
    public $successStatus = 200;

    public function explodeX($delimiters, $string) {
        return explode(chr(1), str_replace($delimiters, chr(1), $string));
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
            $user['access_token'] = $user->createToken('lazysuzy-web')->accessToken;
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
                    'locale' => 'en',
                ]);
                $user->identities()->create([
                    'provider_id'   => $providerUser->getId(),
                    'provider_name' => $provider,
                ]);

                $auth_user =  $user;
                $auth_user['access_token'] = $user->createToken('lazysuzy-web')->accessToken;
                
                return $auth_user;
            }

            return null;
        
        }
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
            'picture' => 'null',
            'locale' => 'null',
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
            'picture' => 'null',
            'locale' => 'null',
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
}
