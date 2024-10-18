<?php

namespace App\Services\Api\V1;

use App\Models\Notification;
use App\Models\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Api\V1\ApiResponseTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

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
        Notification::Notify($user_id, "Congratulations on your successful bank account setup. You can now start making payments.");
        return true;
    }

    public function manuallyAddBank ($request, $user_id)
    {
        $wallet = Wallet::where('user_id', $user_id)->first();

        Notification::Notify($user_id, "Congratulations on your successful bank account setup. You can now start making payments.");

        return $wallet->update([
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'bank_name' => $request->bank_name,
        ]);
    }

    public function setPin(object $request, int $user_id)
    {
        $wallet = Wallet::where('user_id', $user_id)->first();
        $wallet->update([
            'pin' => Hash::make($request->pin),
        ]);
        Notification::Notify($user_id, "Congratulations on your successful withdrawal pin setup. You can now start making payments.");

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
    
                Notification::Notify($user_id, "You just changed your withdrawal pin.");
    
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

            Notification::Notify($user->id, "You just changed your password.");

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
        Notification::Notify($user->id, "You've just updated your profile.");
        return true;
    }

    public function delete (int $user_id)
    {
        $user = User::where('id', $user_id)->first();
        if ($user !== null) {
            Wallet::where('user_id', $user_id)->update(['user_id' => null]);
            Transaction::where('user_id', $user_id)->update(['user_id' => null]);
            Request::where('user_id', $user_id)->update(['user_id' => null]);
            $user->forceDelete();
            return true;
        }
        return false;
    }
}

