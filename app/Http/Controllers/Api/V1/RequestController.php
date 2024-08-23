<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetCategoriesRequest;
use App\Http\Requests\Api\V1\RequestCreationRequest;
use App\Http\Requests\Api\V1\RequestRejectionReasonRequest;
use App\Http\Requests\Api\V1\SearchRequest;
use App\Services\Api\V1\RequestService;
use App\Traits\Api\V1\ApiResponseTrait;

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

    

    public function getUserRequests()
    {
        $res = $this->request_service->getUserRequests(auth()->user()->id);
        return $this->successResponse($res);
    }

    public function search(SearchRequest $request) 
    {
        $res = $this->request_service->search(auth()->user()->id, (Object) $request->validated());
        if ($res !== null) {
            return $this->successResponse($res);
        }
        return $this->notFoundResponse("Request not found!!");
    }

    public function searchAdmin(SearchRequest $request) 
    {
        $res = $this->request_service->searchAdmin((Object) $request->validated());
        if ($res !== null) {
            return $this->successResponse($res);
        }
        return $this->notFoundResponse("Request not found!!");
    }
    
    public function getUserPendingRequests()
    {
        $res = $this->request_service->getUserPendingRequests(auth()->user()->id);
        return $this->successResponse($res);
    }

    public function getUserRequest(string $uuid)
    {
        $res = $this->request_service->getUserRequest($uuid, auth()->user()->id);
        if ($res !== null) {
            return $this->successResponse($res);
        }
        return $this->errorResponse('Request not found.', null, 404);
    }

    public function confirmRequest(RequestRejectionReasonRequest $request, string $uuid, bool $action)
    {
        if ($action == false) {
            $res = $this->request_service->confirmRequest($uuid, $action, (Object) $request->validated());
        }
        $res = $this->request_service->confirmRequest($uuid, $action, (Object) null);
        if ($res === 'treated') {
            return $this->errorResponse('Request has already been accepted or rejected');
            // return $this->successResponse('Request confirmed successfully.');
        } else if ($res === 'reason') {
            return $this->successResponse('Input rejection reason (compulsory) or image.');
        } else if ($res === 'rejected') {
            return $this->successResponse('Request declined successfully.');
        } else if ($res === 'confirmed') {
            return $this->successResponse('Request confirmed successfully.');
        }
    }
}
