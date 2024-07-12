<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetCategoriesRequest;
use App\Http\Requests\Api\V1\RequestCreationRequest;
use App\Services\Api\V1\RequestService;
use App\Traits\Api\V1\ApiResponseTrait;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private RequestService $request_service)
    { 
    }

    public function getBrands ()
    {
        $res = $this->request_service->getBrands();
        return $this->successResponse($res);
    }

    public function getCategories (GetCategoriesRequest $request)
    {
        $_data = (Object) $request->validated();
        $res = $this->request_service->getCategories($_data);
        return $this->successResponse($res);
    }

    public function store (RequestCreationRequest $request)
    {
        $_data = (Object) $request->validated();
        $res = $this->request_service->store($_data, auth()->user()->id);
        if ($res === true) {
            return $this->successResponse('Request created successfully');
        }
        return $this->errorResponse('Error creating request');
    }

    public function getRequests()
    {
        $res = $this->request_service->getRequests();
        return $this->successResponse($res);
    }
    public function getPendingRequests()
    {
        $res = $this->request_service->getPendingRequests();
        return $this->successResponse($res);
    }
    public function getRequest(string $uuid)
    {
        $res = $this->request_service->getRequest($uuid);
        if ($res !== null) {
            return $this->successResponse($res);
        }
        return $this->errorResponse('Request not found.', null, 404);
    }
    public function confirmRequest(string $uuid, bool $action)
    {
        $res = $this->request_service->confirmRequest($uuid, $action);
        if ($res === 'treated') {
            return $this->errorResponse('Request has already been accepted or rejected');
            // return $this->successResponse('Request confirmed successfully.');
        } else if ($res === 'rejected') {
            return $this->successResponse('Request declined successfully.');
        } else if ($res === 'confirmed') {
            return $this->successResponse('Request confirmed successfully.');
        }
    }
}
