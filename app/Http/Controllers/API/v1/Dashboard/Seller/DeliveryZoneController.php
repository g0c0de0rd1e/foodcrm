<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\DeliveryZone\SellerRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\DeliveryZoneResource;
use App\Models\DeliveryZone;
use App\Repositories\DeliveryZoneRepository\DeliveryZoneRepository;
use App\Services\DeliveryZoneService\DeliveryZoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryZoneController extends SellerBaseController
{
    private DeliveryZoneService $service;
    private DeliveryZoneRepository $repository;

    /**
     * @param DeliveryZoneService $service
     * @param DeliveryZoneRepository $repository
     */
    public function __construct(DeliveryZoneService $service, DeliveryZoneRepository $repository)
    {
        parent::__construct();

        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request)
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $deliveryZone = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return DeliveryZoneResource::collection($deliveryZone);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function store(SellerRequest $request): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $exist = DeliveryZone::where('shop_id', $this->shop->id)->first();

        if (!empty($exist)) {
            return $this->update($exist, $request);
        }

        $data = $request->validated();
        $data['shop_id'] = $this->shop->id;

        $result = $this->service->create($data);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_successfully_created'), []);
    }

    /**
     * Display the specified resource.
     *
     * @param DeliveryZone $deliveryZone
     * @return JsonResponse
     */
    public function show(DeliveryZone $deliveryZone): JsonResponse
    {
        if (!$this->shop || data_get($deliveryZone, 'shop_id') !== data_get($this->shop, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        return $this->successResponse(
            __('web.coupon_found'),
            DeliveryZoneResource::make($this->repository->show($deliveryZone))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param DeliveryZone $deliveryZone
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function update(DeliveryZone $deliveryZone, SellerRequest $request): JsonResponse
    {
        if (!$this->shop || data_get($deliveryZone, 'shop_id') !== data_get($this->shop, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $result = $this->service->update($deliveryZone, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_has_been_successfully_updated'), []);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $this->service->delete($request->input('ids', []), $this->shop->id);

        return $this->successResponse(__('web.record_has_been_successfully_delete'));
    }

}
