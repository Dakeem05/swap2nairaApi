<?php

namespace App\Services\Api\V1;

use App\Mail\UserForgotPassword;
use App\Mail\UserVerifyEmail;
use App\Models\Card;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Api\V1\WalletService;
use App\Traits\Api\V1\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CardService
{
    use ApiResponseTrait;

    public function index ()
    {
        $card = Card::paginate(15);
        return $card;
    }

    public function store (Object $request, Int $user_id)
    {
        
        $image = time().'.'.$request->image->getClientOriginalExtension();
        $destinationPath = public_path().'/uploads/images/brandImages/';
        $request->image->move($destinationPath, $image);
        $path = User::url().'/images/'.$image;

        $card = Card::create([
            'brand' => $request->brand,
            'category' => $request->category,
            'type' => $request->type,
            'rate' => $request->rate,
            'image' => $path
        ]);

        return $card;   
    }

    public function show (Int $id)
    {
        $card = Card::find($id);
        return $card;
    }

    public function update (Object $request, Int $id)
    {
        $card = Card::where('id', $id)->first();
        if ($card !== null) {
            $card->update([
                'category' => isset($request->category)? $request->category : $card->category,
                'type' => isset($request->type)? $request->type : $card->type,
                'rate' => isset($request->rate)? $request->rate : $card->rate,
            ]);
            return true;
        }
        return false;
        
    }

    public function delete (Int $id)
    {
        $card = Card::find($id);
        if ($card !== null) {
            $card->forceDelete();
            return true;
        }
        return true;
    }
}

