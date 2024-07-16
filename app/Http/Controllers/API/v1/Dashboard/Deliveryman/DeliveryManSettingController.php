<?php

namespace App\Http\Controllers\API\v1\Dashboard\Deliveryman;

use App\Helpers\ResponseError;
use App\Http\Requests\DeliveryManSetting\DeliveryManRequest;
use App\Http\Requests\DeliveryManSetting\DeliveryManUpdateLocationRequest;
use App\Http\Resources\DeliveryManSettingResource;
use App\Repositories\DeliveryManSettingRepository\DeliveryManSettingRepository;
use App\Services\DeliveryManSettingService\DeliveryManSettingService;
use Illuminate\Http\JsonResponse;

class DeliveryManSettingController extends DeliverymanBaseController
{
    private DeliveryManSettingRepository $repository;
    private DeliveryManSettingService $service;

    public function __construct(DeliveryManSettingRepository $repository, DeliveryManSettingService $service)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DeliveryManRequest $request
     * @return JsonResponse
     */
    public function store(DeliveryManRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->createOrUpdate($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_successfully_created'),
            DeliveryManSettingResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DeliveryManUpdateLocationRequest $request
     * @return JsonResponse
     */
    public function updateLocation(DeliveryManUpdateLocationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->updateLocation($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            DeliveryManSettingResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function online(): JsonResponse
    {
        $result = $this->service->updateOnline();

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_successfully_created'),
            DeliveryManSettingResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $result = $this->repository->detail(null, auth('sanctum')->id());

        if (empty($result)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('web.delivery_man_setting_found'),
            DeliveryManSettingResource::make($result)
        );
    }

}
