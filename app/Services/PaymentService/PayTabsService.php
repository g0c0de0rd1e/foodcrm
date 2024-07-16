<?php

namespace App\Services\PaymentService;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Exception;
use Http;
use Illuminate\Database\Eloquent\Model;
use Str;
use Throwable;

class PayTabsService extends BaseService
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
        $payment        = Payment::where('tag', 'paytabs')->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $order          = Order::find(data_get($data, 'order_id'));
        $totalPrice     = ceil($order->rate_total_price * 2 * 100) / 2;

        $order->update([
            'total_price' => ($totalPrice / $order->rate) / 100
        ]);

        $host = request()->getSchemeAndHttpHost();

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . data_get($payload, 'server_key')
        ];

        $trxRef = "$order->id-" . time();

        $currency = Str::upper($order->currency?->title ?? data_get($payload, 'currency'));

//        if(!in_array($currency, ['AED','EGP','SAR','OMR','JOD','US'])) {
//            throw new Exception(__('errors.' . ResponseError::CURRENCY_NOT_FOUND, locale: $this->language));
//        }

        $data = [
            'amount'                    => $totalPrice,
            'currency'                  => $currency,
            'site_url'                  => config('app.front_url'),
            'return_url'                => "$host/order-razorpay-success?order_id=$order->id",
            'cancel_url'                => "$host/order-razorpay-success?order_id=$order->id",
            'max_amount'                => $totalPrice,
            'min_amount'                => $totalPrice,
            'consumers_email'           => $order->user?->email,
            'consumers_full_name'       => $order->username ?? "{$order->user?->firstname} {$order->user?->lastname}",
            'consumers_phone_number'    => $order->phone ?? $order->user?->phone,
            'address_shipping'          => data_get($order->address, 'address'),
        ];

        $request = Http::withHeaders($headers)->post('https://secure.paytabs.sa/payment/request', [
            'merchant_id'       => '105345',
            'secret_key'        => 'SZJN6JRB6R-JGGWW29DD9-RWKLJNWNGR',
            'site_url'          => config('app.front_url'),
            'return_url'        => "$host/order-razorpay-success?order_id=$order->id",
            'cc_first_name'     => $order->username ?? $order->user?->firstname,
            'cc_last_name'      => $order->username ?? $order->user?->lastname,
            'cc_phone_number'   => $order->phone    ?? $order->user?->phone,
            'cc_email'          => $order->user?->email,
            'amount'            => $totalPrice,
            'currency'          => $currency,
            'msg_lang'          => $this->language,
        ]);

        dd($request);
        $body = json_decode($request->body());

        dd($body);
        if (data_get($body, 'status') === 'error') {
            throw new Exception(data_get($body, 'message'));
        }

        return PaymentProcess::updateOrCreate([
            'user_id'   => auth('sanctum')->id(),
            'order_id'  => data_get($data, 'order_id'),
        ], [
            'id'    => $trxRef,
            'data'  => [
                'url'       => $body,
                'price'     => $totalPrice,
                'order_id'  => $order->id
            ]
        ]);
    }
}
