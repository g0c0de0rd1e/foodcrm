<?php

namespace App\Http\Controllers\API\v1\Dashboard\Deliveryman;

use App\Http\Requests\Order\SellerOrderReportRequest;
use App\Repositories\OrderRepository\DeliveryMan\OrderReportRepository;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;

class OrderReportController extends DeliverymanBaseController
{
    use Notification;

    /**
     * @param OrderReportRepository $repository
     */
    public function __construct(
        private OrderReportRepository $repository
    )
    {
        parent::__construct();
    }

    public function report(SellerOrderReportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['deliveryman'] = auth('sanctum')->id();

        return $this->successResponse(
            __('web.report_found'),
            $this->repository->report($validated)
        );
    }
}
