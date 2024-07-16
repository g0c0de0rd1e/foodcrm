<?php

namespace App\Services\PaymentService;

use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletHistory;
use App\Services\CoreService;
use Illuminate\Support\Str;

class BaseService extends CoreService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    public function afterHook($token, $status) {

        /** @var PaymentProcess $paymentProcess */
        $paymentProcess = PaymentProcess::with(['order.transaction'])
            ->where('id', $token)
            ->first();

        if (!empty($paymentProcess->order_id)) {

            $paymentProcess->order?->transaction?->update([
                'payment_trx_id' => $token,
                'status'         => $status,
            ]);

            return;
        }

        $userId = data_get($paymentProcess->data, 'user_id');
        $type   = data_get($paymentProcess->data, 'type');

        if ($userId && $type === 'wallet') {

            $trxId       = data_get($paymentProcess->data, 'trx_id');
            $transaction = Transaction::find($trxId);

            $transaction->update([
                'payment_trx_id' => $token,
                'status'         => $status,
            ]);

            if ($status === WalletHistory::PAID) {

                $user = User::find($userId);

                $user?->wallet?->increment('price', data_get($paymentProcess->data, 'price'));

                $user->wallet->histories()->create([
                    'uuid'              => Str::uuid(),
                    'transaction_id'    => $transaction->id,
                    'type'              => 'topup',
                    'price'             => $transaction->price,
                    'note'              => "Payment #{$user?->wallet?->id} via Wallet" ,
                    'status'            => WalletHistory::PAID,
                    'created_by'        => $transaction->user_id,
                ]);

            }

        }

    }
}
