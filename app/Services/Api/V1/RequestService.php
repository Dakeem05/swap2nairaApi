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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RequestService
{
    use ApiResponseTrait;

    public function getBrands ()
    {
        $brands = Card::where('active', true)->select('image', 'brand')->get();

        $res = [];
        $trackedBrands = [];
        
        foreach ($brands as $key => $brand) {
            if (!in_array($brand->brand, $trackedBrands)) {
                $arr = [
                    'brand' => $brand->brand,
                    'image' => $brand->image
                ];
                $trackedBrands[] = $brand->brand;
                $res[] = $arr;
            }
        }
        
        return $res;
        
    }

    public function getCountries ($request)
    {
        $types = Card::where('brand', $request->brand)->where('country', $request->country)->where('active', true)->get();
        return $types;
    }

    public function getCategories ($request)
    {
        if (!isset($request->category)) {
            $types = Card::where('brand', $request->brand)->where('country', $request->country)->where('active', true)->get();
            return $types;
        }
        $types = Card::where('brand', $request->brand)->where('country', $request->country)->where('category', $request->category)->where('active', true)->get();
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
        $requests = Request::with('card')->latest()->paginate();
        return $requests;
    }

    public function getPendingRequests ()
    {
        $requests = Request::where('status', 'pending')->with('card')->paginate();
        return $requests;
    }

    public function getRequest (String $uuid)
    {
        $requests = Request::with('card')->where('uuid', $uuid)->first();
        return $requests;
    }
    public function getUserRequests (Int $user_id)
    {
        $requests = Request::where('user_id', $user_id)->with('card')->latest()->paginate();
        return $requests;
    }

    public function getUserPendingRequests (Int $user_id)
    {
        $requests = Request::where('user_id', $user_id)->where('status', 'pending')->with('card')->paginate();
        return $requests;
    }

    public function search (Int $user_id, object $request)
    {
        if (strlen($request->input) == 12) {
            $requests = Request::where('user_id', $user_id)->where('uuid','like','%'.$request->input.'%')->with('card')->latest()->paginate();
            if ($requests == null){
                return null;
            }
    
            return $requests;
        } else {
            $requests = [];
            $cards = Card::where('type','like','%'.$request->input.'%')->latest()->get();

            if ($cards == null){
                return null;
            }
            foreach ($cards as $key => $card) {
                $requests[] = Request::where('user_id', $user_id)->where('card_id',$card->id)->with('card')->first();
            }

            $perPage = 15; 
            $currentPage = request()->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;
        
            $items = array_slice($requests, $offset, $perPage);
            
            $path = url('/api/v1/requests/search'); 
            
            $paginator = new LengthAwarePaginator($items, count($requests), $perPage, $currentPage, [
                'path' => $path,
                'pageName' => 'page',
            ]);
        
            $result = $paginator;

            return $result;
        }
        
    }

    public function searchAdmin (object $request)
    {
        if (strlen($request->input) == 12) {
            $requests = Request::where('uuid','like','%'.$request->input.'%')->with('card')->latest()->paginate();
            if ($requests == null){
                return null;
            }
    
            return $requests;
        } else {
            $requests = [];
            $cards = Card::where('type','like','%'.$request->input.'%')->latest()->get();

            if ($cards == null){
                return null;
            }
            foreach ($cards as $key => $card) {
                $requests[] = Request::where('card_id',$card->id)->with('card')->first();
            }

            $perPage = 15; 
            $currentPage = request()->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;
        
            $items = array_slice($requests, $offset, $perPage);
            
            $path = url('/api/v1/requests/search'); 
            
            $paginator = new LengthAwarePaginator($items, count($requests), $perPage, $currentPage, [
                'path' => $path,
                'pageName' => 'page',
            ]);
        
            $result = $paginator;

            return $result;

        }
        
    }

    public function getUserRequest (String $uuid, Int $user_id)
    {
        $requests = Request::where('user_id', $user_id)->where('uuid', $uuid)->with('card')->first();
        return $requests;
    }

    public function confirmRequest (String $uuid, bool $action, Object $data)
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

            if (isset($data->reason)) {
                $image = '';
                if (isset($data->image)) {

                    $imagee = time().'.'.$data->image->getClientOriginalExtension();
                    $destinationPath = public_path().'/uploads/images/rejectionImages/';
                    $data->image->move($destinationPath, $imagee);
                    $path = User::url().'/images/'.$imagee;
                    $image = $path;                 
                    $request->update([
                        'rejection_image' => $image,
                    ]);
                }

                $request->update([
                    'rejection_reason' => $data->reason,
                    'status' => 'declined'
                ]);
                $transaction->update([
                    'status' => 'declined'
                ]);
                Mail::to($user->email)->send(new UserSellRequestRejection($name, $request->number, $card->type, $card->rate, $sum, $data->reason, $image == "" ? null : $image));
                Notification::Notify($user_id, "Your gift card sell request of $request->number of $card->type at $card->rate each totaling $sum has been rejected. Check the email sent to you with the reason of the rejection.");
        
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $key => $admin) {
                    Mail::to($admin->email)->send(new AdminSellRequestRejection($name, $request->number, $card->type, $card->rate, $sum));
                    Notification::Notify($admin->id, "Gift card sell request of $request->number of $card->type at $card->rate each totaling $sum has been rejected. The reason for the rejection has been emailed to the user.");
                }
                return 'rejected';
            }
            return 'reason';
        }
        return 'treated';
    }
}

