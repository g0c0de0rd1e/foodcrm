<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\SmsPayload\UpdateRequest;
use App\Http\Requests\SmsPayload\StoreRequest;
use App\Http\Resources\SmsPayloadResource;
use App\Repositories\SmsPayloadRepository\SmsPayloadRepository;
use App\Services\SmsPayloadService\SmsPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SmsPayloadController extends AdminBaseController
{
    public function __construct(private SmsPayloadService $service, private SmsPayloadRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return SmsPayloadResource::collection($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return data_get($result, 'params') ?
                $this->requestErrorResponse(
                    data_get($result, 'status'),
                    data_get($result, 'code'),
                    data_get($result, 'params'),
                    data_get($result, 'http', 422),
                )
                : $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_successfully_created'),
            SmsPayloadResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param string $smsType
     * @return JsonResponse
     */
    public function show(string $smsType): JsonResponse
    {
        $model = $this->repository->show($smsType);

        if (empty($model)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('web.coupon_found'),
            SmsPayloadResource::make($model)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param string $smsType
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(string $smsType, UpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($smsType, $validated);

        if (!data_get($result, 'status')) {
            return data_get($result, 'params') ?
                $this->requestErrorResponse(
                    data_get($result, 'status'),
                    data_get($result, 'code'),
                    data_get($result, 'params'),
                    data_get($result, 'http', 422),
                )
                : $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('web.record_has_been_successfully_updated'),
            SmsPayloadResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('web.record_has_been_successfully_delete'));
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
