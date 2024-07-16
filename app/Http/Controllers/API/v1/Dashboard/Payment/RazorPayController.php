<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Http\Controllers\Controller;
use App\Models\WalletHistory;
use App\Services\PaymentService\RazorPayService;
use App\Traits\ApiResponse;
use App\Traits\OnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redirect;
use Throwable;

class RazorPayController extends Controller
{
    use OnResponse, ApiResponse;

    public function __construct(private RazorPayService $service)
    {
        parent::__construct();
    }

    /**
     * process transaction.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function orderProcessTransaction(Request $request): JsonResponse
    {
        try {
            $paymentProcess = $this->service->orderProcessTransaction($request->all());

            return $this->successResponse('success', $paymentProcess);
        } catch (Throwable $e) {
            return $this->onErrorResponse(['message' => $e->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function orderResultTransaction(Request $request): RedirectResponse
    {
        $orderId = (int)$request->input('order_id');

        $to = config('app.front_url') . "orders/$orderId";

        return Redirect::to($to);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function paymentWebHook(Request $request): void
    {
        $token  = $request->input('payload.payment_link.entity.id');
        $status = $request->input('payload.payment_link.entity.status');

        $status = match ($status) {
            'cancelled', 'expired'        => WalletHistory::CANCELED,
            'paid'                        => WalletHistory::PAID,
            default => 'progress',
        };

        $this->service->afterHook($token, $status);
    }

}
