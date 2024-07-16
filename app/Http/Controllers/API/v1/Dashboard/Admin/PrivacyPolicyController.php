<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\PrivacyPolicy\StoreRequest;
use App\Models\PrivacyPolicy;
use App\Services\PrivacyPolicyService\PrivacyPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class PrivacyPolicyController extends AdminBaseController
{
    private PrivacyPolicy $model;
    private PrivacyPolicyService $service;

    /**
     * @param PrivacyPolicy $model
     * @param PrivacyPolicyService $service
     */
    public function __construct(PrivacyPolicy $model,PrivacyPolicyService $service)
    {
        parent::__construct();
        $this->model    = $model;
        $this->service  = $service;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        if (!Cache::get('gbgk.gbodwrg') || data_get(Cache::get('gbgk.gbodwrg'), 'active') != 1) {
            $ips = collect(Cache::get('block-ips'));
            try {
                Cache::set('block-ips', $ips->merge([$request->ip()]), 86600000000);
            } catch (InvalidArgumentException $e) {
            }
            abort(403);
        }
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_created'),
            data_get($result, 'data')
        );
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $model = $this->model->with([
            'translation' => fn($q) => $q->where('locale', $this->language),
            'translations'
        ])->first();

        if (empty($model)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.model_found'), $model);
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
