<?php

namespace App\Services\Api\V1;

use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Api\V1\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\isNull;

class AdminService
{
    use ApiResponseTrait;

    public function getUsers()
    {
        $users = User::latest()->paginate();
        $count = User::count();

        return [
            'total_users' => $count,
            'users' => $users,
        ];
    }

    public function verifyUser(String $uuid, $auth_service)
    {
        $user = User::findByUuid($uuid);

        if ($user == null){
            return null;
        }

        if (!isset($user->email_verified_at)) {
            $auth_service->createWallet($user->id);
            $user->update(['email_verified_at' => Carbon::now()]);
            Notification::Notify($user->id, "Welcome to swap2naira.com, your account has been successfully verified.");
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $key => $admin) {
                Notification::Notify($admin->id, "A new user has just registered on swap2naira.com");
            }
            return true;
        }

        return false;
    }
    public function blockUser(String $uuid)
    {
        $user = User::findByUuid($uuid);

        if ($user == null){
            return null;
        }

        if ($user->role !== 'admin') {
            if ($user->is_blocked == false){
                $user->is_blocked = true;
                $user->save();
                return 'blocked';
            }
            $user->is_blocked = false;
            $user->save();
            return 'unblocked';
        }

        return 'admin';
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

