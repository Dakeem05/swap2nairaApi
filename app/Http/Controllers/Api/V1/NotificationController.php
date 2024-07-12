<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Traits\Api\V1\ApiResponseTrait;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $_notifications = Notification::where('user_id', auth()->user()->id)->latest()->paginate();
        return $this->successResponse($_notifications);
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $_notification = Notification::find($id);
        if ($_notification !== null) {
            $_notification->update([
                'is_read' => true
            ]);
            return $this->successResponse($_notification);
        }
        return $this->errorResponse('Notification not found', null, 404);
    }

    public function read (string $id)
    {
        $_notification =  Notification::where('id', $id)->first();
        if ($_notification !== null) {
            $_notification->update([
                'is_read' => true
            ]);
            return $this->successResponse('Notification has been marked as read.');
        }
        return $this->errorResponse('Notification not found', null, 404);
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $_notification = Notification::find($id);
        if ($_notification !== null) {
            $_notification->delete();
            return $this->successResponse('Notification deleted successfully');
        }
        return $this->errorResponse('Notification not found', null, 404);
    }
}
