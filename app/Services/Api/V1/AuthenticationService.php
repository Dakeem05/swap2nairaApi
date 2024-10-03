<?php

namespace App\Services\Api\V1;

use App\Mail\UserForgotPassword;
use App\Mail\UserVerifyEmail;
use App\Models\Notification;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\Api\V1\WalletService;
use App\Traits\Api\V1\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthenticationService
{
    use ApiResponseTrait;

    public function register (object $user_data)
    {
        $user = User::create([
            'username' => $user_data->username,
            'email' => $user_data->email,
            'password' => Hash::make($user_data->password),
            'phone' => $user_data->phone,
            'referrer_code' => isset($user_data->referral_code) ? $user_data->referral_code : null,
        ]);
        
        $otp = PasswordResetToken::GenerateOtp($user->email);
        
        Mail::to($user->email)->send(new UserVerifyEmail($user->email, $user->username, $otp));
        
        return $user;
    }

    public function resend (object $user_data)
    {
        $user = User::where('email', $user_data->email)->where('email_verified_at', null)->first();
        
        if ($user !== null){
            $otp = PasswordResetToken::GenerateOtp($user->email);
            Mail::to($user->email)->send(new UserVerifyEmail($user->email, $user->username, $otp));
            return true;
        } else {
            return false;
        }
    }

    public function verify (object $user_data)
    {
        $user = User::where('email' , $user_data->email)->first();
        $instance = PasswordResetToken::where('email', $user_data->email)->first();
        if ($instance !== null){
            if($user_data->otp == $instance->token){
                $this->createWallet($user->id);
                $user->update(['email_verified_at' => Carbon::now()]);
                $instance->delete();
                Notification::Notify($user->id, "Welcome to swap2naira.com, your account has been successfully verified.");
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $key => $admin) {
                    Notification::Notify($admin->id, "A new user has just registered on swap2naira.com");
                }

                return true;
            } else {
                return false;
            }    
        } else {
            return false;
        }
    }

    public function forgotPassword (object $user_data)
    {
        $user = User::where('email', $user_data->email)->first();
        
        if ($user !== null){
            $otp = PasswordResetToken::GenerateOtp($user->email);
            Notification::Notify($user->id, "You have just requested for a password reset.");
            Mail::to($user->email)->send(new UserForgotPassword($user->email, $user->username, $otp));
            return true;
        } else {
            return false;
        }
    }

    public function verifyForgot (object $user_data)
    {
        $user = User::where('email' , $user_data->email)->first();
        $instance = PasswordResetToken::where('email', $user_data->email)->first();
        if ($instance !== null){
            if($user_data->otp == $instance->token){
                $instance->otp_verified_at = Carbon::now();
                $instance->save();
                return true;
            } else {
                return false;
            }    
        } else {
            return false;
        }
    }

    public function changePassword (object $user_data)
    {
        $user = User::where('email' , $user_data->email)->first();
        $instance = PasswordResetToken::where('email', $user_data->email)->first();
        if ($instance !== null){
            if ($instance->otp_verified_at !== null){
                $user->update([
                'password' => Hash::make($user_data->password),
            ]);
            Notification::Notify($user->id, "You have just changed your password.");
            $instance->delete();
            return true;
        } else {
            return false;
        }
        } else {
            return false;
        }
        
    }

    public function createWallet($user_id)
    {
        $wallet_service = new WalletService();
        $wallet_service->createWallet($user_id);
    }
}

