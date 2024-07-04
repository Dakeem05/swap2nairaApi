<?php

namespace App\Services\Api\V1;

use App\Models\Wallet;

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
        $wallet = Wallet::where('user_id', $user_id)->first();

        if ($wallet) {
            return $wallet->main_balance;
        }

        return 0;
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
}