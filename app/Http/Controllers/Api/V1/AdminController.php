<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\AdminService;
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
