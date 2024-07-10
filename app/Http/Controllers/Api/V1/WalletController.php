<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WithdrawalRequest;
use App\Services\Api\V1\WalletService;
use App\Traits\Api\V1\ApiResponseTrait;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private WalletService $wallet_service)
    {
    }

    public function getUserBalance ()
    {
        $res = $this->wallet_service->getWalletBalance(auth()->user()->id);
        return $this->successResponse($res);
    }

    public function withdraw (WithdrawalRequest $request)
    {
        $_data = (Object) $request->validated();
        $res = $this->wallet_service->withdraw($request, auth()->user()->id);
        // return $res;
        if ($res === true) {
            return $this->successResponse('Withdrawal successful!!');
        } else if ($res === 'pin') {
            return $this->errorResponse('Please set up your withdrawal pin.');
        } else if ($res === 'account') {
            return $this->errorResponse('Please add your bank account.');
        } else if ($res === 'insufficient') {
            return $this->errorResponse('Insufficient balance.');
        } else if ($res === 'wrong') {
            return $this->errorResponse('Incorrect withdrawal pin.');
        }
        return $this->errorResponse('Error processing request!!');
    }
}
