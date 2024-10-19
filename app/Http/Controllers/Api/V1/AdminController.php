<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RequestRejectionReasonRequest;
use App\Http\Requests\Api\V1\SearchRequest;
use App\Http\Requests\Api\V1\UpdateUserBalanceRequest;
use App\Services\Api\V1\AdminService;
use App\Services\Api\V1\AuthenticationService;
use App\Traits\Api\V1\ApiResponseTrait;

class AdminController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AdminService $admin_service)
    {
        
    }

    public function getUsers()
    {
        $res = $this->admin_service->getUsers();
        return $this->successResponse($res);
    }

    public function dashboard()
    {
        $res = $this->admin_service->dashboard();
        return $this->successResponse($res);
    }
    
    public function updateUserBalance(UpdateUserBalanceRequest $request) 
    {
        $res = $this->admin_service->updateUserBalance((Object) $request->validated());
        if ($res === true) {
            return $this->successResponse('User balance updated successfully.');
        } else if ($res === false) {
            return $this->errorResponse('An error occurred');
        } else if ($res === 'unverified') {
            return $this->errorResponse('This user is not verified yet.');
        }
        return $this->notFoundResponse("User not found!!");
    }

    public function searchForUser(SearchRequest $request) 
    {
        $res = $this->admin_service->searchForUser((Object) $request->validated());
        if ($res !== null) {
            return $this->successResponse($res);
        }
        return $this->notFoundResponse("User not found!!");
    }

    public function userTransactions(String $uuid) 
    {
        $res = $this->admin_service->userTransactions($uuid);
        if ($res !== null) {
            return $this->successResponse($res);
        }
        return $this->notFoundResponse("User not found!!");
    }

    public function verifyUser(string $uuid, AuthenticationService $auth_service)
    {
        $res = $this->admin_service->verifyUser($uuid, $auth_service);
        if ($res === true) {
            return $this->successResponse('User verified successfully.');
        } else if ($res === false) {
            return $this->errorResponse('User is already verified');
        }
        return $this->notFoundResponse("User not found!!");
    }

    public function blockUser(string $uuid)
    {
        $res = $this->admin_service->blockUser($uuid);
        if ($res === 'blocked') {
            return $this->successResponse('User blocked successfully.');
        } else if ($res === 'unblocked') {
            return $this->successResponse('User unblocked successfully.');
        } else if ($res === 'admin') {
            return $this->errorResponse('Can\'t block an admin.');
        }
        return $this->notFoundResponse("User not found");
    }

    public function getUser(string $uuid)
    {
        $res = $this->admin_service->getUser($uuid);

        if ($res !== null) {
            return $this->successResponse($res);
        }
        return $this->errorResponse('User not found.', null, 404);
    }

    public function getTransactions()
    {
        $res = $this->admin_service->getTransactions();
        return $this->successResponse($res);
    }
    public function getPendingTransactions()
    {
        $res = $this->admin_service->getPendingTransactions();
        return $this->successResponse($res);
    }
    public function getTransaction(string $uuid)
    {
        $res = $this->admin_service->getTransaction($uuid);
        if ($res !== null) {
            return $this->successResponse($res);
        }
        return $this->errorResponse('Transaction not found.', null, 404);
    }

    public function getWithdrawalTransactions ()
    {
        $res = $this->admin_service->getWithdrawalTransactions();
        return $this->successResponse($res);
    }

    public function searchAdmin(SearchRequest $request) 
    {
        $res = $this->admin_service->searchAdmin((Object) $request->validated());
        if ($res !== null) {
            return $this->successResponse($res);
        }
        return $this->notFoundResponse("Transaction not found!!");
    }

    public function withdrawalAction(RequestRejectionReasonRequest $request, string $uuid, string $action)
    {
        if ($action == false) {
            $res = $this->admin_service->withdrawalAction($uuid, $action, (Object) $request->validated());
        }
        $res = $this->admin_service->withdrawalAction($uuid, $action, (Object) null);

        if ($res === 'treated') {
            return $this->errorResponse('Withdrawal request has already been accepted or rejected');
        } else if ($res === 'confirmed') {
            return $this->successResponse('Request confirmed successfully.');
        } else if ($res === 'reason') {
            return $this->successResponse('Input rejection reason (compulsory) or image.');
        } else if ($res === 'rejected') {
            return $this->successResponse('Request declined successfully.');
        }
        return $this->notFoundResponse('Transaction not found.');
    }
}
