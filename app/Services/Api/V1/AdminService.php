<?php

namespace App\Services\Api\V1;

use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Api\V1\ApiResponseTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AdminService
{
    use ApiResponseTrait;

    public function getUsers()
    {
        $users = User::paginate();
        $count = User::count();

        return [
            'total_users' => $count,
            'users' => $users,
        ];
    }

    public function getUser(String $uuid)
    {
        $user = User::findByUuid($uuid);
        return $user;
    }

    public function getTransactions ()
    {
        $transactions = Transaction::latest()->paginate();
        return $transactions;
    }

    public function getPendingTransactions ()
    {
        $transactions = Transaction::where('status', 'pending')->paginate();
        return $transactions;
    }

    public function getTransaction (String $uuid)
    {
        $transactions = Transaction::findByUuid($uuid);
        return $transactions;
    }
}

