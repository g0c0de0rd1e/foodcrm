<?php

namespace App\Services\PaymentService;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use DB;
use Illuminate\Database\Eloquent\Model;
use Razorpay\Api\Api;
use Str;
use Throwable;

class RazorPayService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array $data
     * @return PaymentProcess|Model
     * @throws Throwable
     */
    public function orderProcessTransaction(array $data): Model|PaymentProcess
    {
        return DB::transaction(function () use ($data) {

            $host           = request()->getSchemeAndHttpHost();

            $payment        = Payment::where('tag', 'razorpay')->first();
            $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
            $payload        = $paymentPayload?->payload;

            $key            = data_get($paymentPayload?->payload, 'razorpay_key');
            $secret         = data_get($paymentPayload?->payload, 'razorpay_secret');

            $api            = new Api($key, $secret);

            $order          = Order::find(data_get($data, 'order_id'));

            $totalPrice     = ceil($order->rate_total_price * 2 * 100) / 2;

            $paymentLink    = $api->paymentLink->create([
                'amount'                    => $totalPrice,
                'currency'                  => Str::upper($order->currency?->title ?? data_get($payload, 'currency')),
                'accept_partial'            => false,
                'first_min_partial_amount'  => $totalPrice,
                'description'               => "For #$order->id",
                'callback_url'              => "$host/order-razorpay-success?order_id=$order->id",
                'callback_method'           => 'get'
            ]);

            $order->update([
                'total_price' => ($totalPrice / $order->rate) / 100
            ]);

            return PaymentProcess::updateOrCreate([
                'user_id'   => auth('sanctum')->id(),
                'order_id'  => data_get($data, 'order_id'),
            ], [
                'id'    => data_get($paymentLink, 'id'),
                'data'  => [
                    'url'   => data_get($paymentLink, 'short_url'),
                    'price' => $totalPrice,
                    'order_id' => $order->id
                ]
            ]);
        });
    }
}
