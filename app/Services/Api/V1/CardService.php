<?php

namespace App\Services\Api\V1;

use App\Traits\Api\V1\ApiResponseTrait;
use App\Models\Card;
use App\Models\User;

class CardService
{
    use ApiResponseTrait;

    public function index ()
    {
        $card = Card::paginate(15);
        return $card;
    }

    public function checkIfGiftCardExists ($brand)
    {
        $card = Card::where('brand', $brand)->exists();
        if ($card) {
            return true;
        }
        return false;
    }


    public function store (Object $request, Int $user_id)
    {
        
        if (isset($request->image)) {
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
        } else{
            $instance = Card::where('brand', $request->brand)->first();
            $card = Card::create([
                'brand' => $request->brand,
                'category' => $request->category,
                'type' => $request->type,
                'rate' => $request->rate,
                'image' => $instance->image
            ]);
        }
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
                'active' => isset($request->is_active)? $request->is_active : $card->active,
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

