<?php

namespace App\Services\Api\V1;

use App\Traits\Api\V1\ApiResponseTrait;
use App\Models\Card;
use App\Models\User;

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
}

