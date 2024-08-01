<?php

namespace App\Services\Api\V1;

use App\Mail\AdminWithdrawRequest;
use App\Mail\UserWithdrawRequest;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Ramsey\Uuid\Type\Integer;

class WalletService
{
    public function createWallet(int $user_id, $type = 'main')
    {
        if (! in_array($type, ['main', 'sub'])) {
            return false;
        }

        return Wallet::create([
            'user_id' => $user_id,
            'type' => $type,
        ]);
    }

    public function getWalletBalance(int $user_id)
    {
        $wallet = Wallet::where('user_id', $user_id)->select('main_balance')->first();

        return $wallet;
    }

    public function withdraw (Object $request, Int $user_id)
    {
        $wallet = Wallet::where('user_id', $user_id)->with('user')->first();

        // return $wallet->main_balance;
        if ($wallet->pin === null) {
            return 'pin';
        } else if ($wallet->account_number === null && $wallet->account_name === null) {
            return 'account';
        } else if ($this->senderBalanceIsSufficient($user_id, $request->amount) == false) {
            return 'insufficient';
        } else if (Hash::check($request->pin, $wallet->pin) == false){
            return 'wrong';
        }

        try {
            $random = 'swap2naira_'.Str::random(20);

            $data = array(
                "account_bank"=> $wallet->bank_code,
                "account_number"=> $wallet->account_number,
                "amount"=> $request->amount,
                "currency"=> "NGN",
                "debit_currency"=> "NGN",
                "reference"=> $random,
                "narration" => "Swap2Naira Transfer ₦".$request->amount
            );

            $response = Http::withHeaders([
                "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
                "Cache-Control" => 'no-cache',
            ])->post(env('FLW_PAYMENT_URL').'/transfers', $data);
            $res = json_decode($response->getBody());
            
            // if (true == true) {
            if ($res->data->is_approved == true) {
                $this->updateWalletBalance($user_id, $request->amount, 'debit');
                Transaction::create([
                    'user_id' => $user_id,
                    'request_id' => null,
                    'amount' => $request->amount,
                    'flw_fee' => $res->data->fee,
                    'flw_status' => $res->data->status,
                    'reference' => $res->data->reference,
                    'type' => 'withdrawal',
                    'tnx_id' => $res->data->id
                ]);
                $name = strtoupper($wallet->user->name !== null ? $wallet->user->name : $wallet->user->username);
                Notification::Notify($user_id, "You just requested withdrawal of ₦".$request->amount.'.');
                Mail::to($wallet->user->email)->send(new UserWithdrawRequest($name, $request->amount, $wallet->main_balance));
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $key => $admin) {
                    Mail::to($admin->email)->send(new AdminWithdrawRequest($name, $request->amount));
                    Notification::Notify($admin->id, $wallet->user->name !== null ? $wallet->user->name : $wallet->user->username." just requested withdrawal of ₦".$request->amount.'.');
                }
                // sleep(20);
                // $response_retrieve = Http::withHeaders([
                //     "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
                //     "Cache-Control" => 'no-cache',
                // ])->get(env('FLW_PAYMENT_URL').'/v3/transfers/'.$res->data->id);
                // $res_retrieve = json_decode($response_retrieve->getBody());
        
                // $transfer = Transfer::find($transfer_id->id);
                // $transfer->update(['status'=> strtolower($res_retrieve->data->status)]);
        
                // // $message_array = explode(":", $res_retrieve->data->complete_message);
        
                // if(strtolower($res_retrieve->data->status) == "failed"){
                //     return ApiResponse::errorResponse($res_retrieve->data->complete_message);
                // } else if (strtolower($res_retrieve->data->status) == "successful") {
                //     return ApiResponse::successResponse([
                //         'mesage'=>$res_retrieve->data->complete_message,
                //         'data' => $res_retrieve->data
                //     ]);
                // } else {
                //     return ApiResponse::successResponse($res_retrieve->data->complete_message);
                // }
                return true;
            }
            return false;
        } catch (\Exception $th) {
            throw $th; 
            return false;
        }

    }

    public function updateWalletBalance(int $user_id, float $amount, string $type)
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

    public function senderBalanceIsSufficient($user_id, $amount)
    {
        $wallet_service = new WalletService();
        $wallet_balance = $wallet_service->getWalletBalance($user_id);
        
        if ($wallet_balance->main_balance >= $amount) {
            return true;
        }

        return false;
    }

    public function flwWebhook (Object $request)
    {
        $secretHash = config('services.flutterwave.secret_hash');
        $signature = $request->header('verif-hash');
        if (!$signature || ($signature !== $secretHash)) {
            abort(401);
        }
        $payload = $request->all();

        Log::info('Received webhook event: ', $payload);

        if (isset($payload['event']) && $payload['event'] === 'transfer.completed') {
            $data = $payload['data'];
            $transactionId = $data['id'];
            $accountNumber = $data['account_number'];
            $bankName = $data['bank_name'];
            $amount = $data['amount'];
            $status = $data['status'];
            $reference = $data['reference'];
            $narration = $data['narration'];
            $completeMessage = $data['complete_message'];

            $transaction = Transaction::where('type', 'withdrawal')->where('reference', $reference)->where('status', 'pending')->where('flw_status', 'NEW')->first();
            if ($transaction !== null) {
                if ($status === 'FAILED') {
                    $transaction->update([
                        'status' => 'declined',
                        'flw_status' => $status,
                    ]);
                    $this->updateWalletBalance($transaction->user_id, $amount, 'credit');
                    Log::error("Transaction failed: $completeMessage");
                } elseif ($status === 'SUCCESSFUL') {
                    $transaction->update([
                        'status' => 'confirmed',
                        'flw_status' => $status,
                    ]);

                    Log::info("Transaction successful: $reference");

                } else {
                    Log::warning("Transaction status unknown: $status");
                    abort(401);
                }
                return response(200);
            }  
            abort(401);
        }
        abort(401);
    }

    public function getTransactions (Integer $user_id)
    {
        $transactions = Transaction::where('user_id', $user_id)->latest()->paginate();
        return $transactions;
    }

    public function getPendingTransactions (Integer $user_id)
    {
        $transactions = Transaction::where('user_id', $user_id)->where('status', 'pending')->paginate();
        return $transactions;
    }

    public function getTransaction (String $uuid, Integer $user_id)
    {
        $transactions = Transaction::where('user_id', $user_id)->findByUuid($uuid);
        return $transactions;
    }
}