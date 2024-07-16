<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StripeRequest;
use App\Services\PaymentService\PayStackService;
use App\Traits\ApiResponse;
use App\Traits\OnResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redirect;
use Throwable;

class PayStackController extends Controller
{
    use OnResponse, ApiResponse;

    public function __construct(private PayStackService $service)
    {
        parent::__construct();
    }

    /**
     * process transaction.
     *
     * @param StripeRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function orderProcessTransaction(StripeRequest $request): JsonResponse
    {
        try {
            $result = $this->service->orderProcessTransaction($request->all());

            return $this->successResponse('success', $result);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage()]);
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
     * @return void
     */
    public function paymentWebHook(): void
    {
        $ips = ['52.31.139.75', '52.49.173.169', '52.214.14.220']; // but I got 213.230.97.92

        $status = request()->input('event');

        $status = match ($status) {
            'charge.success'    => 'paid',
            default             => 'progress',
        };

        $token = request()->input('data.reference');

        $this->service->afterHook($token, $status);
    }

}
