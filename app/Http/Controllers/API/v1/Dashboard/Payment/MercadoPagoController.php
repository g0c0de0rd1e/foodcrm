<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StripeRequest;
use App\Models\WalletHistory;
use App\Services\PaymentService\MercadoPagoService;
use App\Traits\ApiResponse;
use App\Traits\OnResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Log;
use Redirect;
use Throwable;

class MercadoPagoController extends Controller
{
    use OnResponse, ApiResponse;

    public function __construct(private MercadoPagoService $service)
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
    public function orderProcessTransaction(Request $request): JsonResponse
    {
        try {
            $result = $this->service->orderProcessTransaction($request->all());

            return $this->successResponse('success', $result);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_501,
                'message' => $e->getMessage(),
            ]);
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
        Log::error('mercado pago', [
            'all'   => $request->all(),
            'reAll' => request()->all(),
            'input' => @file_get_contents("php://input")
        ]);

        $status = $request->input('data.status');

        $status = match ($status) {
            'succeeded', 'successful', 'success'                         => WalletHistory::PAID,
            'failed', 'cancelled', 'reversed', 'chargeback', 'disputed'  => WalletHistory::CANCELED,
            default                                                      => 'progress',
        };

        $token = $request->input('data.id');

        $this->service->afterHook($token, $status);

    }

}
