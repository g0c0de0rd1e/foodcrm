<?php
namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\PayStackCallbackUrlRequest;
use App\Http\Requests\Payment\RazorPayCallbackUrlRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function payStackCallbackUrl(PayStackCallbackUrlRequest $request) {

        Log::info('payStack Callback', [$request->all()]);

        $transaction = Transaction::where('payment_trx_id', $request->input('reference'))->first();

        $transaction->update([
            'status'                => 'paid',
            'status_description'    => 'Successfully',
        ]);

        return response()->json('ok');
    }

    public function razorpayCallbackUrl(RazorPayCallbackUrlRequest $request) {

        Log::info('Razorpay Callback', [$request->all()]);

        $status = $request->input('razorpay_payment_link_status');

        $transaction = Transaction::where('payment_trx_id', $request->input('razorpay_payment_link_id'))
            ->first();

        $transaction->update([
            'status'                => $status == 'paid' ? 'paid' : 'rejected',
            'status_description'    => $status == 'paid' ? 'Success' : $status,
        ] + $status === 'paid' ? ['payment_sys_trans_id'  => $request->input('razorpay_payment_id')] : []
        );

    }
}
