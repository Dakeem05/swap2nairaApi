<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddBankRequest;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\ChangePinRequest;
use App\Http\Requests\Api\V1\ManuallyAddBankRequest;
use App\Http\Requests\Api\V1\ResolveBankRequest;
use App\Http\Requests\Api\V1\SetPinRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Services\Api\V1\ProfileService;
use App\Traits\Api\V1\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct (private ProfileService $profile_service)
    {
    }

    public function banks()
    {
        $res = $this->profile_service->banks();
        return $this->successResponse($res->data);
    }

    public function resolveAccount(ResolveBankRequest $request)
    {
        $_data = (Object) $request->validated();

        $res = $this->profile_service->resolveAccount($_data);
        return $this->successResponse($res->data);
    }
    
    public function addBankAccount(AddBankRequest $request)
    {
        $_data = (Object) $request->validated();
    
        $res = $this->profile_service->addBankAccount($_data, auth()->user()->id);
        if ($res) {
            return $this->successResponse('Bank account added successfully.');
        }
        return $this->serverErrorResponse('An error occurred.');
    }

    public function manuallyAddBank (ManuallyAddBankRequest $request)
    {
        $res = $this->profile_service->manuallyAddBank((object) $request->validated(), auth()->user()->id);
        if ($res) {
            return $this->successResponse('Bank details added successfully');
        }
        return $this->errorResponse('An error occured while adding bank details');
    }

    public function setPin(SetPinRequest $request)
    {
        $_data = (Object) $request->validated();
    
        $res = $this->profile_service->setPin($_data, auth()->user()->id);
        if ($res) {
            return $this->successResponse('Pin setup successfully.');
        }
        return $this->serverErrorResponse('An error occurred.');
    }

    public function changePin(ChangePinRequest $request)
    {
        $_data = (Object) $request->validated();

        $res = $this->profile_service->changePin($_data, auth()->user()->id);
        if ($res) {
            return $this->successResponse('Pin changed successfully.');
        }
        return $this->serverErrorResponse('Wrong pin or an error occurred.');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $_data = (Object) $request->validated();

        $res = $this->profile_service->changePassword($_data, auth()->user()->id);
        if ($res) {
            return $this->successResponse('Password changed successfully.');
        }
        return $this->serverErrorResponse('Wrong password.');
    }

    public function updateProfile (UpdateProfileRequest $request)
    {
        $_data = (Object) $request->validated();

        $res = $this->profile_service->updateProfile($_data, auth()->user()->id);
        if ($res) {
            return $this->successResponse('Profile updated successfully.');
        }
        return $this->serverErrorResponse('An error occurred.');
    }

    public function delete ()
    {
        $res = $this->profile_service->delete(auth()->user()->id);
        if ($res) {
            return $this->successResponse('Profile deleted successfully.');
        }
        return $this->serverErrorResponse('An error occurred.');
    }
}
