<?php

namespace App\Services\Api\V1;

use App\Mail\AdminSellRequest;
use App\Mail\AdminSellRequestRejection;
use App\Mail\AdminSellRequestVerfication;
use App\Mail\UserSellRequest;
use App\Mail\UserSellRequestRejection;
use App\Mail\UserSellRequestVerfication;
use App\Traits\Api\V1\ApiResponseTrait;
use App\Models\Card;
use App\Models\Notification;
use App\Models\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RequestService
{
    use ApiResponseTrait;

    public function getBrands ()
    {
        $brands = Card::select('brand')->select('image')->get();

        $res = ['brand' => []];

        foreach ($brands as $key => $brand) {
            if (!in_array($brand->brand, $res['brand'])) {
                $arr = [
                    'brand' => $brand->brand,
                    'image' => $brand->image
                ];
                $res['brand'][] = $brand->brand;  // Add brand to the array to avoid duplicates
                $res[] = $arr;
            }
        }

        return $res;
    }

    public function getCategories ($request)
    {
        if (!isset($request->category)) {
            $types = Card::where('brand', $request->brand)->select('type', 'rate', 'category')->get();
            return $types;
        }
        $types = Card::where('brand', $request->brand)->where('category', $request->category)->select('type', 'rate', 'category')->get();
        return $types;
    }

    public function store (Object $request, Int $user_id)
    {
        $contains_ecodes = false;
        $ecodes = [];

        if (isset($request->ecodes)) {
            foreach ($request->ecodes as $key => $ecode) {
                $ecodes[] = $ecode;
                $contains_ecodes = true;
            }
        }

        $contains_images = false;
        $images = [];

        if (isset($request->images)) {
            foreach ($request->images as $key => $image) {
                $imagee = time().'.'.$image->getClientOriginalExtension();
                $destinationPath = public_path().'/uploads/images/physicalCards/';
                $image->move($destinationPath, $imagee);
                $path = User::url().'/images/'.$imagee;
                $images[] = $path;
                $contains_images = true;
            }
        }

        $card = Card::where('id', $request->card_id)->first();
        $request_data = Request::create([
            'user_id' => $user_id,
            'card_id' => $request->card_id,
            'rate' => $card->rate,
            'number' => $request->number,
            'total_amount' => $card->rate * $request->number,
            'images' => $contains_images ? $images : null,
            'ecodes' => $contains_ecodes? $ecodes : null,
        ]);

        $sum = $card->rate * $request->number;
        $random = 'swap2naira_'.Str::random(20);
        $user = User::where('id', $user_id)->first();
        $name = strtoupper($user->name !== null ? $user->name : $user->username);
        Transaction::create([
            'user_id' => $user_id,
            'request_id' => $request_data->id,
            'amount' => $sum,
            'reference' => $random,
            'type' => 'giftcard',
        ]);
        Mail::to($user->email)->send(new UserSellRequest($name, $request->number, $card->type, $card->rate, $sum));
        Notification::Notify($user_id, "Your gift card sell request of $request->number of $card->type at ₦$card->rate each totaling ₦$sum has been successfully submitted, Admins would verify it and would credit your wallet.");

        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $key => $admin) {
            Mail::to($admin->email)->send(new AdminSellRequest($name, $request->number, $card->type, $card->rate, $sum));
            Notification::Notify($admin->id, "Gift card sell request of $request->number of $card->type at ₦$card->rate each totaling ₦$sum has been made.");
        }

        return true;
    }

    public function getRequests ()
    {
        $requests = Request::latest()->paginate();
        return $requests;
    }

    public function getPendingRequests ()
    {
        $requests = Request::where('status', 'pending')->paginate();
        return $requests;
    }

    public function getRequest (String $uuid)
    {
        $requests = Request::findByUuid($uuid);
        return $requests;
    }

    public function confirmRequest (String $uuid, bool $action)
    {
        $request = Request::where('status', 'pending')->where('uuid', $uuid)->first();
        if ($request !== null) {
            $transaction = Transaction::where('request_id', $request->id)->first();
            $card = Card::where('id', $request->card_id)->first();
            $user_id = $request->user_id;
            $user = User::where('id', $user_id)->first();
            $name = strtoupper($user->name !== null ? $user->name : $user->username);
            $sum = $card->rate * $request->number;
        
            if ($action == true) {
                $request->update([
                    'status' => 'confirmed'
                ]);
                $wallet = Wallet::where('user_id', $request->user_id)->first();
                $wallet->increment('main_balance', $request->total_amount);
                $transaction->update([
                    'status' => 'confirmed'
                ]);

                Mail::to($user->email)->send(new UserSellRequestVerfication($name, $request->number, $card->type, $card->rate, $sum));
                Notification::Notify($user_id, "Your gift card sell request of $request->number of $card->type at $card->rate each totaling $sum has been confirmed and your wallet has been credited.");
        
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $key => $admin) {
                    Mail::to($admin->email)->send(new AdminSellRequestVerfication($name, $request->number, $card->type, $card->rate, $sum));
                    Notification::Notify($admin->id, "Gift card sell request of $request->number of $card->type at $card->rate each totaling $sum has been confirmed.");
                }

                return 'confirmed';
            }
            $request->update([
                'status' => 'declined'
            ]);
            $transaction->update([
                'status' => 'declined'
            ]);
            Mail::to($user->email)->send(new UserSellRequestRejection($name, $request->number, $card->type, $card->rate, $sum));
            Notification::Notify($user_id, "Your gift card sell request of $request->number of $card->type at $card->rate each totaling $sum has been rejected.");
    
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $key => $admin) {
                Mail::to($admin->email)->send(new AdminSellRequestRejection($name, $request->number, $card->type, $card->rate, $sum));
                Notification::Notify($admin->id, "Gift card sell request of $request->number of $card->type at $card->rate each totaling $sum has been rejected.");
            }
            return 'rejected';
        }
        return 'treated';
    }
}

