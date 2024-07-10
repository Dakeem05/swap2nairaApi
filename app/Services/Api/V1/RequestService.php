<?php

namespace App\Services\Api\V1;

use App\Mail\AdminSellRequest;
use App\Mail\UserSellRequest;
use App\Traits\Api\V1\ApiResponseTrait;
use App\Models\Card;
use App\Models\Notification;
use App\Models\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class RequestService
{
    use ApiResponseTrait;

    public function getBrands ()
    {
        $brands = Card::select('brand')->get();
        $res = [];

        foreach ($brands as $key => $brand) {
            if (!in_array($brand->brand, $res)) {
                $res[] = $brand->brand;
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
        
        $user = User::where('id', $user_id)->first();
        $name = strtoupper($user->name !== null ? $user->name : $user->username);

        Mail::to($user->email)->send(new UserSellRequest($name, $request->number, $card->type, $card->rate, $sum));
        Notification::Notify($user_id, "Your gift card sell request of $request->number of $card->type at $card->rate each totaling $sum has been successfully submitted, Admins would verify it and would credit your wallet.");

        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $key => $admin) {
            Mail::to($admin->email)->send(new AdminSellRequest($name, $request->number, $card->type, $card->rate, $sum));
            Notification::Notify($admin->id, "Gift card sell request of $request->number of $card->type at $card->rate each totaling $sum has been made.");
        }

        return true;
    }
}

