<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\TermCondition\StoreRequest;
use App\Models\TermCondition;
use App\Services\TermService\TermService;
use Illuminate\Http\JsonResponse;

class TermsController extends AdminBaseController
{
    private TermService $service;

    /**
     * @param TermService $service
     */
    public function __construct(TermService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $termCondition = $this->service->create($request->validated());

        if (!data_get($termCondition, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_501]);
        }

        return $this->successResponse(__('web.record_has_been_successfully_created'), $termCondition);
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $termCondition = TermCondition::with('translations')->first();

        return $this->successResponse(__('web.model_found'), $termCondition);
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
