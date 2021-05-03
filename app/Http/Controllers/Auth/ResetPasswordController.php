<?php

namespace App\Http\Controllers\Auth;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Http\Controllers\Controller;
use App\Models\Utility;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    public function create(Request $request) {
        $validate = $request->validate([
            'email' => 'required|string|email' 
        ]);

        if(isset($validate->errors))
            return response()->json($validate, 404);
        
        $user =  User::where('email', $request->email)->first();
        
        if(!$user) 
            return response()->json([
                'message' => 'We can\'t find user with this email address.' 
            ], 404);
        
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Utility::generateID()
            ]
        );

        if($user && $passwordReset)
            $user->notify(new PasswordResetRequest($passwordReset));
        
        return response()->json([
            'message' => 'Password Reset Email Sent',
            'status' => true
        ], 200);
    }

    // find the active token and reset the pass
    public function find($token) {
        $passwordReset = PasswordReset::where('token', $token)->first();

        if(!$passwordReset)
            return response()->json([
                'status' => false,
                'message' => 'Invalid reset token'
            ], 404);
        
        if(Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'status' => false,
                'message' => 'Reset token expired.'
            ], 404);
        }

        return response()->json([
            $passwordReset
        ], 200);
    }

    // reset password
    public function reset(Request $request) {
        $validate = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|confirmed',
            'token' => 'required|string'
        ]);

        if(isset($validate->errors))
            return response()->json($validate, 404);
        
        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->email]
        ])->first();

        if (!$passwordReset)
            return response()->json([
                'message' => 'This password reset token is invalid.'
            ], 404);
        $user = User::where('email', $passwordReset->email)->first();
        if (!$user)
            return response()->json([
                'message' => 'We can\'t find a user with that e-mail address.'
            ], 404);
            
        $user->password = bcrypt($request->password);
        $user->save();

        $passwordReset->delete();
        $user->notify(new PasswordResetSuccess($passwordReset));
        
        return response()->json($user);
    }
}
