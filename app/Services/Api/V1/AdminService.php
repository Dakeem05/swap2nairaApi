<?php

namespace App\Services\Api\V1;

use App\Mail\AdminWithdrawalRequestRejection;
use App\Mail\AdminWithdrawRequestPayment;
use App\Mail\UserWithdrawalRequestRejection;
use App\Mail\UserWithdrawRequestPayment;
use App\Models\Notification;
use App\Models\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Api\V1\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

use function PHPUnit\Framework\isNull;

class AdminService
{
    use ApiResponseTrait;

    public function getUsers()
    {
        $users = User::with(['wallet' => function ($query) {
            $query->select('id', 'user_id', 'main_balance');
        }])->latest()->paginate();
        $count = User::count();

        return [
            'total_users' => $count,
            'users' => $users,
        ];
    }

    public function dashboard()
    {
        $user_count = User::count();
        $pending_request_count = Request::where('status', 'pending')->count();
        $request_count = Request::count();
        $transaction_count = Transaction::count();
        $pending_requests = Request::where('status', 'pending')->with('card')->take(5)->get();
        $confirmed_requests = Request::where('status', 'confirmed')->get();
        $withdrawals = Transaction::where('status', 'confirmed')->where('type', 'withdrawal')->get();

        $array_of_confirmed_requests_amounts = [];
        $array_of_withdrawal_amounts = [];

        foreach ($confirmed_requests as $key => $confirmed_request) {
            $array_of_confirmed_requests_amounts[] = $confirmed_request->total_amount;
        }

        foreach ($withdrawals as $key => $withdrawal) {
            $array_of_withdrawal_amounts[] = $withdrawal->total_amount;
        }

        return [
            'total_users' => $user_count,
            'total_pending_requests' => $pending_request_count,
            'total_requests' => $request_count,
            'total_transactions' => $transaction_count,
            'pending_requests' => $pending_requests,
            'total_amount_made' => array_sum($array_of_confirmed_requests_amounts),
            'total_amount_withdrawn' => array_sum($array_of_withdrawal_amounts),
        ];
    }

    public function updateUserBalance (object $request)
    {
        $user = User::findByUuid($request->uuid);

        if ($user == null){
            return null;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        if ($wallet == null){
            return 'unverified';
        }

        $wallet->main_balance = $request->balance;
        $wallet->save();

        return true;
    }

    public function userTransactions (string $uuid)
    {
        $user = User::findByUuid($uuid);

        if ($user == null){
            return null;
        }
        
        $transactions = Transaction::where('user_id', $user->id)->latest()->paginate();
        return $transactions;
    }

    public function searchForUser (object $request)
    {
        $user = User::where('username','like','%'.$request->input.'%')->orWhere('email','like','%'.$request->input.'%')->orWhere('phone','like','%'.$request->input.'%')->with('wallet')->latest()->paginate();

        if ($user == null){
            return null;
        }

        return $user;
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
        $user = User::where('uuid', $uuid)->with(['wallet' => function ($query) {
            $query->select('id', 'user_id', 'main_balance');
        }])->with('transactions')->first();
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

    public function searchAdmin (object $request)
    {
        $transactions = Transaction::where('uuid','like','%'.$request->input.'%')->latest()->paginate();
        
        if ($transactions == null){
            return null;
        }

        return $transactions;
        
    }

    public function withdrawalAction (string $uuid, bool $action, Object $data)
    {
        $transaction = Transaction::where('uuid', $uuid)->first();

        if ($transaction == null){
            return null;
        }

        if ($transaction->status !== 'pending') {
            return 'treated';
        }

        if ($action == true) {
            $transaction->update([
                'status' => 'confirmed',
                'sent_mail' => true
            ]);
            $wallet = Wallet::where('user_id', $transaction->user_id)->with('user')->first();
            $name = strtoupper($wallet->user->name !== null ? $wallet->user->name : $wallet->user->username);
            Notification::Notify($transaction->user_id, "Your requested withdrawal of ₦".$transaction->amount. ' has been paid.');
            Mail::to($wallet->user->email)->send(new UserWithdrawRequestPayment($name, $transaction->amount, $wallet->main_balance));
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $key => $admin) {
                Mail::to($admin->email)->send(new AdminWithdrawRequestPayment($name, $transaction->amount));
                Notification::Notify($admin->id, "$name's request withdrawal of ₦$transaction->amount has been paid.");
            }

            return 'confirmed';
        }
        
        if (isset($data->reason)) {
            $image = '';
            if (isset($data->image)) {

                $imagee = time().'.'.$data->image->getClientOriginalExtension();
                $destinationPath = public_path().'/uploads/images/rejectionImages/';
                $data->image->move($destinationPath, $imagee);
                $path = env('APP_URL').'/images/'.$imagee;
                $image = $path;                 
                $transaction->update([
                    'rejection_image' => $image,
                ]);
            }
            
            $transaction->update([
                'rejection_reason' => $data->reason,
                'status' => 'declined',
                'sent_mail' => true
            ]);


            $this->updateWalletBalance($transaction->user_id, $transaction->amount, 'credit');
            $wallet = Wallet::where('user_id', $transaction->user_id)->with('user')->first();
            $name = strtoupper($wallet->user->name !== null ? $wallet->user->name : $wallet->user->username);
            Notification::Notify($transaction->user_id, "Your requested withdrawal of ₦".$transaction->amount.' has been rejected. Check the email sent to you with the reason of the rejection.');
            Mail::to($wallet->user->email)->send(new UserWithdrawalRequestRejection($name, $transaction->amount, $wallet->main_balance, $data->reason, $image == "" ? null : $image));
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $key => $admin) {
                Mail::to($admin->email)->send(new AdminWithdrawalRequestRejection($name, $transaction->amount));
                Notification::Notify($admin->id, "$name's request withdrawal of ₦$transaction->amount has been rejected. The reason for the rejection has been emailed to the user.");
            }
            return 'rejected';
        }
        return 'reason';
    }

    private function updateWalletBalance(int $user_id, float $amount, string $type)
    {
        $wallet = Wallet::where('user_id', $user_id)->first();

        if ($wallet) {
            $opening_balance = $wallet->main_balance;
            $closing_balance = $type === 'credit' ? $opening_balance + $amount : $opening_balance - $amount;

            return $wallet->update([
                'main_balance' => $closing_balance,
            ]);
        }

        return false;
    }
}

