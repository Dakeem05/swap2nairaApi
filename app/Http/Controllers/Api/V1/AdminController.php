<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

    public function verifyUser(string $uuid, AuthenticationService $auth_service)
    {
        $res = $this->admin_service->verifyUser($uuid, $auth_service);
        if ($res === true) {
            return $this->successResponse('User verified successfully.');
        } else if ($res === false) {
            return $this->errorResponse('User is already verified');
        }
        return $this->notFoundResponse("User not found");
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
}
