<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CardCreationRequest;
use App\Http\Requests\Api\V1\CardEditRequest;
use App\Services\Api\V1\CardService;
use App\Traits\Api\V1\ApiResponseTrait;
use Illuminate\Http\Request;

class CardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private CardService $card_service)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $res = $this->card_service->index();
        return $this->successResponse($res);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CardCreationRequest $request)
    {
        $_data = (Object) $request->validated();
    
        $res = $this->card_service->store($_data, auth()->user()->id);
        if ($res !== null) {
            return $this->successResponse($res, 'Card created successfully.');
        }
        return $this->serverErrorResponse('An error occurred.');
    }

    public function getGiftCardBrands()
    {
        $brands = config('brands');

        return $this->successResponse($brands);
    }

    public function checkIfGiftCardExists(String $brand)
    {
        $res = $this->card_service->checkIfGiftCardExists($brand);
        if ($res === true) {
            return $this->successResponse('Card exists');
        }
        return $this->errorResponse('Card does not exist');
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $res = $this->card_service->show($id);
        return $this->successResponse($res);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CardEditRequest $request, string $id)
    {
        $_data = (Object) $request->validated();

        $res = $this->card_service->update($_data, $id);
        if ($res) {
            return $this->successResponse($res, 'Card updated successfully.');
        }
        return $this->serverErrorResponse('An error occurred.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $res = $this->card_service->delete($id);
        if ($res) {
            return $this->successResponse($res, 'Card deleted successfully.');
        }
        return $this->serverErrorResponse('An error occurred.');
    }

    public function toggleActiveState(string $id)
    {
        $res = $this->card_service->toggleActiveState($id);
        if ($res) {
            return $this->successResponse($res, 'Card active state changed successfully.');
        }
        return $this->serverErrorResponse('An error occurred.');
    }
}
