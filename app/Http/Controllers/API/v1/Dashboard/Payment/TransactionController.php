<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\ResponseError;
use App\Http\Requests\Payment\TransactionRequest;
use App\Http\Requests\Payment\TransactionUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\WalletResource;
use App\Models\Order;
use App\Models\PaymentProcess;
use App\Models\Transaction;
use App\Services\PaymentService\PayPalService;
use App\Services\TransactionService\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

class TransactionController extends PaymentBaseController
{
    public function store(string $type, int $id, TransactionRequest $request): JsonResponse
    {
        if ($type === 'order') {

            $result = (new TransactionService)->orderTransaction($id, $request->validated());

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            return $this->successResponse(
                __('web.record_successfully_created'),
                OrderResource::make(data_get($result, 'data'))
            );

        }

        $result = (new TransactionService)->walletTransaction($id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_successfully_created'),
            WalletResource::make(data_get($result, 'data'))
        );
    }

    public function updateStatus(int $id, TransactionUpdateRequest $request): JsonResponse
    {
        /** @var Order $order */
        $order = Order::with('transactions')->find($id);

        if (!$order) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        if (!$order->transaction) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_501,
                'message'   => 'Transaction is not created'
            ]);
        }

        $paymentProcess = PaymentProcess::find($request->input('token'));

        if (empty($paymentProcess) && !in_array($order->transaction->paymentSystem?->tag, ['cash', 'wallet'])) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => 'Order not paid']);
        }

        /** @var Transaction $transaction */
        $order->transaction->update([
            'status' => $request->input('status')
        ]);

        $paymentProcess?->delete();

        return $this->successResponse('Success', $order->fresh('transactions'));

//        $paypal = (new PayPalService)->checkOrderStatus($transaction->payment_trx_id);
//
//        if (data_get($paypal, 'data.status')) {
//
//            (new PayPalService)->updateOrderStatus(data_get($paypal, 'data'), $transaction);
//
//            return $this->successResponse(
//                __('web.record_has_been_successfully_updated'),
//                OrderResource::make($order->fresh('transaction'))
//            );
//
//        }
//
//        return $this->onErrorResponse([
//            'status'  => false,
//            'code'    => ResponseError::ERROR_400,
//            'message' => data_get($paypal, 'data.message', 'Something went wrong')
//        ]);
    }

    public function created(Request $request) {
        Log::error('created', $request->all());

        $transaction = Transaction::find($request->input('id'));

        if (!empty($transaction)) {
            (new PayPalService)->updateOrderStatus($request->all(), $transaction);
        }

        dd($request->all());
    }
}
