<?php

namespace App\Services\Api\V1;

use App\Mail\UserForgotPassword;
use App\Mail\UserVerifyEmail;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Api\V1\WalletService;
use App\Traits\Api\V1\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProfileService
{
    use ApiResponseTrait;

    public function banks ()
    {
        $response = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->get(env('FLW_PAYMENT_URL').'/banks/NG');
        return json_decode($response->getBody());
    }

    public function resolveAccount(object $request)
    {
        $data = array(
            "account_number"=> $request->account_number,
            "account_bank"=> $request->bank_code,
        );
    
        $response_account = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->post(env('FLW_PAYMENT_URL').'/accounts/resolve', $data);
        return json_decode($response_account->getBody());
        
    }

    public function addBankAccount(object $request, int $user_id)
    {
        $wallet = Wallet::where('user_id', $user_id)->first();
        $wallet->update([
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'bank_code' => $request->bank_code,
            'bank_name' => $request->bank_name,
        ]);
        return true;
    }

    public function setPin(object $request, int $user_id)
    {
        $wallet = Wallet::where('user_id', $user_id)->first();
        $wallet->update([
            'pin' => Hash::make($request->pin),
        ]);
        return true;
    }

    public function changePin(object $request, int $user_id)
    {
        $wallet = Wallet::where('user_id', $user_id)->first();
        if ($wallet->pin !== null) {
            if (Hash::check($request->old_pin, $wallet->pin)) {
                $wallet->update([
                    'pin' => Hash::make($request->new_pin),
                ]);
    
                //carry out notifications here later
    
                return true;
            }
            return false;
        }
        return false;
    }

    public function changePassword(object $request, int $user_id)
    {
        $user = User::where('id', $user_id)->first();
        if (Hash::check($request->old_password, $user->password)) {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            //carry out notifications here later

            return true;
        }
        return false;
    }

    public function updateProfile (object $request, int $user_id)
    {
        $user = User::where('id', $user_id)->first();
        
        if (isset($request->image)) {
            $image = time().'.'.$request->image->getClientOriginalExtension();
            $destinationPath = public_path().'/uploads/images/profileImages/';
            $request->image->move($destinationPath, $image);
            $path = User::url().'/images/'.$image;
            $user->update([
                'picture' => $path
            ]);
        }
        $user->update([
            'phone' => isset($request->phone)? $request->phone : $user->phone,
            'birthdate' => isset($request->birthdate)? $request->birthdate : $user->birthdate,
            'birthmonth' => isset($request->birthmonth)? $request->birthmonth : $user->birthmonth,
            'name' => isset($request->name)? $request->name : $user->name,
        ]);

        //notify user of changes

        return true;
    }

    public function delete (int $user_id)
    {
        $user = User::where('id', $user_id)->first();
        if ($user !== null) {
            $user->forceDelete();
            return true;
        }
        return false;
    }
}

