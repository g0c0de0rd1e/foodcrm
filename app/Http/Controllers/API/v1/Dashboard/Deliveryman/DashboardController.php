<?php

namespace App\Http\Controllers\API\v1\Dashboard\Deliveryman;

use App\Repositories\DashboardRepository\DashboardRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends DeliverymanBaseController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function countStatistics(Request $request): JsonResponse
    {
        return $this->successResponse(
            __('web.statistics_count'),
            (new DashboardRepository)->orderByStatusStatistics(
                $request->merge(['deliveryman' => auth('sanctum')->id()])->all()
            )
        );
    }
}
