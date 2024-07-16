<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Services\WalletHistoryService\WalletService;
use Illuminate\Http\JsonResponse;

class WalletController extends AdminBaseController
{
    private WalletService $service;

    public function __construct(WalletService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

    /**
     * @return JsonResponse
     */
    public function truncate(): JsonResponse
    {
        $this->service->truncate();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }

    /**
     * @return JsonResponse
     */
    public function restoreAll(): JsonResponse
    {
        $this->service->restoreAll();

        return $this->successResponse(__('web.record_was_successfully_updated'), []);
    }
}
