<?php
namespace App\Services\PaymentService;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Matscode\Paystack\Transaction;
use Str;
use Throwable;

class PayStackService extends BaseService
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
        $payment = Payment::where('tag', 'paystack')->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $transaction    = new Transaction(data_get($payload, 'paystack_sk'));

        $order          = Order::find(data_get($data, 'order_id'));
        $totalPrice     = ceil($order->rate_total_price * 2 * 100) / 2;

        $order->update([
            'total_price' => ($totalPrice / $order->rate) / 100
        ]);

        $host = request()->getSchemeAndHttpHost();

        $data = [
            'email'     => $order->user?->email,
            'amount'    => $totalPrice,
            'currency'  => Str::upper($order->currency?->title ?? data_get($payload, 'currency')),
        ];

        $response = $transaction
            ->setCallbackUrl("$host/order-paystack-success?order_id=$order->id")
            ->initialize($data);

        if (isset($response?->status) && !data_get($response, 'status')) {
            throw new Exception(data_get($response, 'message', 'PayStack server error'));
        }

        return PaymentProcess::updateOrCreate([
            'user_id'   => auth('sanctum')->id(),
            'order_id'  => $order->id,
        ], [
            'id' => data_get($response, 'reference'),
            'data' => [
                'url'   => data_get($response, 'authorizationUrl'),
                'price' => $totalPrice,
                'order_id' => $order->id
            ]
        ]);
    }
}
