<?php

namespace App\Http\Controllers\Auth;

use Socialite;
use Auth;
use App\Models\User;
use App\Models\SocialIdentity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->redirectTo = url()->previous();
        $this->middleware('guest', ['except' => 'logout']);
    }

    public function index(Request $request)
    {
        return view('auth\signup');
    }
    
    /**
     * Redirect the user to the provider authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($driver)
    {
        return Socialite::driver($driver)->redirect();
    }

    /**
     * Obtain the user information from provider.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback($driver)
    {
        try {
            $user = Socialite::driver($driver)->user();
        } catch (\Exception $e) {
            if ($driver == 'google') {
                $user = Socialite::driver($driver)->stateless()->user();
            }
            else {
                $user = Socialite::driver($driver)->user();
            }
        }

       
        $existingUser = $this->findOrCreateUser($user, $driver);
        Auth::login($existingUser, true);

        if (Auth::check()) setcookie('__user_id', Auth::user()->id);
        return redirect($this->redirectPath());
    }
    
    // explode() - will work on multiple delimeters 
    public function explodeX( $delimiters, $string )
    {
        return explode(chr(1), str_replace($delimiters, chr(1), $string));
    }

    public function findOrCreateUser($providerUser, $provider)
    {

        $auth_user = null;
        $account = SocialIdentity::whereProviderName($provider)
                  ->whereProviderId($providerUser->getId())
                  ->first();

        echo json_encode($providerUser);
        die();
        if ($account) {
            $auth_user = $account->user;
        } else {
           $user = User::whereEmail($providerUser->getEmail())->first();
           $f_l_name = $this->explodeX(array(' ', '_'), $providerUser->getName());

           if (! $user) {
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
           }

           $user->identities()->create([
               'provider_id'   => $providerUser->getId(),
               'provider_name' => $provider,
           ]);
            
           $auth_user =  $user;
        }

        return $auth_user;
    }
    protected function sendFailedLoginResponse(Request $request)
    {   
        $redirect_url = $this->redirectPath();
        if (strpos($redirect_url, "error") === false && strpos($redirect_url, "?") === false) {
            $redirect_url .=  "?error=login";
        }
        if (strpos($redirect_url, "?") !== false && strpos($redirect_url, "?error=login") === false) {
            $redirect_url .= "&error=login";
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ])->redirectTo($redirect_url);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $_COOKIE['user_id'] = NULL;
        return redirect($this->redirectPath());
    }
}
